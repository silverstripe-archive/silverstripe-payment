<?php

include_once('EwayConfig.inc.php');

class EwayXMLPayment extends Payment {
	
	// Eway Informations
	
	protected static $privacy_link = 'http://www.eway.com.au/Company/About/Privacy.aspx';
	
	protected static $logo = 'ecommerce/images/payments/eway.gif';
	
	// Test Mode
	
	protected static $test_credit_card_number = '4444333322221111';
	
	protected static $test_amount = '1000';
	
	protected static $test_mode = false;
	
	static function set_test_mode() {self::$test_mode = true;}
	
	// Payment Informations
	
	protected static $customer_id;
	
	static function set_customer_id($id) {self::$customer_id = $id;}
	
	protected static $cvn_mode = true;
	
	static function unset_cvn_mode() {self::$cvn_mode = false;}
	
	private static $test_total_amount = 'The test Total Amount should end in 00 or 08 to get a successful response and has to be in cents (e.g. 1000 for $10.00 or 1008 for $10.08) - all other amounts will return a failed response.';
	
	function getPaymentFormFields() {
		$logo = '<img src="' . self::$logo . '" alt="Credit card payments powered by Eway"/>';
		$privacyLink = '<a href="' . self::$privacy_link . '" target="_blank" title="Read Eway\'s privacy policy">' . $logo . '</a><br/>';
		$fields = new FieldSet(
			new LiteralField('EwayInfo', $privacyLink),
			new LiteralField(
				'EwayXMLPaymentsList',
				'<img src="ecommerce/images/payments/methods/visa.jpg" alt="Visa"/>' .
				'<img src="ecommerce/images/payments/methods/mastercard.jpg" alt="MasterCard"/>' .
				'<img src="ecommerce/images/payments/methods/american-express.gif" alt="American Express"/>' .
				'<img src="ecommerce/images/payments/methods/dinners-club.jpg" alt="Dinners Club"/>' .
				'<img src="ecommerce/images/payments/methods/jcb.jpg" alt="JCB"/>'
			),
			new TextField('Eway_CreditCardHolderName', 'Credit Card Holder Name :')
		);
		if(self::$test_mode) $fields->push(new ReadonlyField('Eway_CreditCardNumber', 'Credit Card Number :', self::$test_credit_card_number));
		else $fields->push(new CreditCardField('Eway_CreditCardNumber', 'Credit Card Number :'));
		$fields->push(new TextField('Eway_CreditCardExpiry', 'Credit Card Expiry : (MMYY)', '', 4));
		$fields->push(new DropdownField(
			'Eway_CreditCardType',
			'Credit Card Type :',
			array(
				'VISA' => 'Visa',
				'MASTERCARD' => 'MasterCard',
				'AMEX' => 'Amex',
				'DINERS' => 'Diners',
				'JCB' => 'JCB'
			)
		));
		if(self::$cvn_mode) $fields->push(new TextField('Eway_CreditCardCVN', 'Credit Card CVN : (3 or 4 digits)', '', 4));
		return $fields;
	}
	
	function getPaymentFormRequirements() {
		$jsCode = <<<JS
			require('Eway_CreditCardHolderName');
			require('Eway_CreditCardNumber');
			require('Eway_CreditCardExpiry');
JS;
		$phpCode = '
			$this->requireField("Eway_CreditCardHolderName", $data);
			$this->requireField("Eway_CreditCardNumber", $data);
			$this->requireField("Eway_CreditCardExpiry", $data);
		';
		if(self::$cvn_mode) {
			Requirements::javascript('ecommerce/javascript/payments/Eway.js');
			$jsCode .= <<<JS
				var ewayCreditCardType = document.getElementsByName('Eway_CreditCardType')[0];
				if(ewayCreditCardType.value == 'VISA' || ewayCreditCardType.value == 'MASTERCARD' || ewayCreditCardType.value == 'AMEX') require('Eway_CreditCardCVN');
JS;
			/*$phpCode .= '
				if(in_array($data["Eway_CreditCardType"], array("VISA", "MASTERCARD", "AMEX")) $this->requireField("Eway_CreditCardCVN", $data);
			';*/
		}
		return array('js' => $jsCode, 'php' => $phpCode);
	}
	
	function processPayment($data, $form) {
		
		// 1) Main Informations
		
		$member = $this->Member();
		
		// 2) Mandatory Settings
		
		$inputs['CardHoldersName'] = $data['Eway_CreditCardHolderName'];
		$inputs['CardNumber'] = self::$test_mode ? self::$test_credit_card_number : implode('', $data['Eway_CreditCardNumber']);
		$inputs['CardExpiryMonth'] = substr($data['Eway_CreditCardExpiry'], 0, 2);
		$inputs['CardExpiryYear'] = substr($data['Eway_CreditCardExpiry'], 2, 2);
		if(self::$cvn_mode) $inputs['CVN'] = $data['Eway_CreditCardCVN'];
		$inputs['TotalAmount'] = self::$test_mode ? self::$test_amount : $this->Amount * 100;
		
		// 3) Payment Informations
		
		$inputs['CustomerInvoiceRef'] = $this->ID;
		
		// 4) Customer Informations
		
		$inputs['CustomerFirstName'] = $data['FirstName'];
		$inputs['CustomerLastName'] = $data['Surname'];
		$inputs['CustomerEmail'] = $data['Email'];
		$inputs['CustomerAddress'] = $data['Address'] . ' ' . $data['AddressLine2'] . ' ' . $data['City'] . ' ' . $data['Country'];
		
		$inputs['CustomerPostcode'] = $member->hasMethod('getPostCode') ? $member->getPostCode() : ''; 
		
		// 5) Empty Informations
		
		$inputs['CustomerInvoiceDescription'] = '';
		$inputs['TrxnNumber'] = '';
		$inputs['Option1'] = '';
		$inputs['Option2'] = '';
		$inputs['Option3'] = '';
		
		// 6) Eway Payment Creation
		
		$customerID = self::$test_mode ? EWAY_DEFAULT_CUSTOMER_ID : self::$customer_id;
		$paymentMethod = self::$cvn_mode ? REAL_TIME_CVN : REAL_TIME;
		$eway = new EwayPaymentLive();
		$eway->EwayPaymentLive($customerID, $paymentMethod, ! self::$test_mode);
		
		foreach ($inputs as $name => $value) $eway->setTransactionData($name, $value);
		
		// 7) Eway Transaction Sending
		
  		$ewayResponseFields = $eway->doPayment();
		
		// 8) Eway Response Management
		
		if($ewayTrxnStatus = strtolower($ewayResponseFields['EWAYTRXNSTATUS'])) {
			$this->TxnRef = $ewayResponseFields['EWAYTRXNNUMBER'];
			$this->Message = $ewayResponseFields['EWAYTRXNERROR'];
			if($ewayTrxnStatus == 'true') {
				$this->Status = 'Success';
				$result = new Payment_Success();
			}
			else {
				$this->Status = 'Failure';
				$result = new Payment_Failure('The payment has not been completed correctly. An invalid response has been received from the Eway payment.');
			}
		}
		else {
			$this->Status = 'Failure';
			$result = new Payment_Failure('The payment has not been completed correctly. The payment request has not been sent to the Eway server correctly.');
		}
		
		$this->write();
		return $result;
	}
}

?>