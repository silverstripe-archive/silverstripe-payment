<?php

/**
 * Payment type to support credit-card payments through Paystation. This uses the direct ("2 party") mode, keeping the payment within
 * the SS site.
 * 
 * @package payment
 */	

class PaystationPayment extends Payment {

	static $db = array(
		"TxnNo" => "Varchar",
		"ReceiptNo" => "Varchar"
	);

	// Paystation information
	
	protected static $privacy_link = 'http://paystation.co.nz/privacy-policy';
	
	protected static $logo = 'payment/images/payments/paystation.jpg';

	// URLs

	protected static $url = 'https://www.paystation.co.nz/direct/paystation.dll?paystation';
	
	// Payment Informations
	
	protected static $paystationMerchantID;
	protected static $paystationGatewayID;
	
	static function set_account($paystationMerchantID, $paystationGatewayID) {
		self::$paystationMerchantID = $paystationMerchantID;
		self::$paystationGatewayID = $paystationGatewayID;
	}

	protected static $test_mode = false;

	static function set_test_mode() {
		self::$test_mode = true;
	}

	// Paystation API doesn't support CVN.
	protected static $cvn_mode = false;

	static function unset_cvn_mode() {
		self::$cvn_mode = false;
	}
	
	protected static $credit_cards = array(
		'Visa' => 'payment/images/payments/methods/visa.jpg',
		'MasterCard' => 'payment/images/payments/methods/mastercard.jpg',
		'American Express' => 'payment/images/payments/methods/american-express.gif',
		'Dinners Club' => 'payment/images/payments/methods/dinners-club.jpg',
		'JCB' => 'payment/images/payments/methods/jcb.jpg'
	);

	static function remove_credit_card($creditCard) {
		unset(self::$credit_cards[$creditCard]);
	}

	function getPaymentFormFields() {
		$logo = '<img src="' . self::$logo . '" alt="Credit card payments powered by DPS"/>';
		$privacyLink = '<a href="' . self::$privacy_link . '" target="_blank" title="Read DPS\'s privacy policy">' . $logo . '</a><br/>';
		$paymentsList = '';
		foreach(self::$credit_cards as $name => $image) $paymentsList .= '<img src="' . $image . '" alt="' . $name . '"/>';
		$fields = new FieldSet(
			new LiteralField('PaystationInfo', $privacyLink),
			new LiteralField('PaystationPaymentsList', $paymentsList),
			new CreditCardField('Paystation_CreditCardNumber', 'Credit Card Number :'),
			new TextField('Paystation_CreditCardExpiry', 'Credit Card Expiry : (MMYY)', '', 4)
		);
		if(self::$cvn_mode) $fields->push(new TextField('Paystation_CreditCardCVN', 'Credit Card CVN : (3 or 4 digits)', '', 4));
		return $fields;
	}

	/**
	 * Returns the required fields to add to the order form, when using this payment method. 
	 */
	function getPaymentFormRequirements() {
		$jsCode = <<<JS
			require('Paystation_CreditCardNumber');
			require('Paystation_CreditCardExpiry');
JS;
		$phpCode = '
			$this->requireField("Paystation_CreditCardNumber", $data);
			$this->requireField("Paystation_CreditCardExpiry", $data);
		';
		return array('js' => $jsCode, 'php' => $phpCode);
	}
		
	function processPayment($data, $form) {
	
		// 1) Main Settings

		
/* pstn_pi		REQUIRED   		String value				The Paystation ID for the account that the payments will be made against
 * pstn_gi		REQUIRED		String value				The Gateway ID that the payments will be made against
 * pstn_ms		REQUIRED		String value  				Merchant Session Ð a unique identification code for each financial transaction request.
 * 															Used to identify the transaction when tracing transactions. Must be unique for each 
 *															attempt at every transaction.
 * pstn_am		REQUIRED		Integer or decimal			Transaction amount. If no Amount Format is specified the amount must be cents (eg.
 * 															$12.35 will be passed as 1235) 
 * pstn_cn		REQUIRED		Integer only (no spaces)	All 16 (for visa/mastercard) digits of the card number. For example 5123456789012346 
 * 								 
 * pstn_ex		REQUIRED		Integer						Card Expiry  The format of which is determined by Date Format, but defaults to YYMM 
 * pstn_2p		REQUIRED		Single character ÔtÕ or ÔTÕ	Two party transaction type. Specifies that you wish to begin a two party transaction.
 * pstn_nr		REQUIRED		Single character ÔtÕ or ÔTÕ	Tells the Paystation application that this transaction will not be redirected back to the
 * 								  							merchants redirect URL. 
 * pstn_df		optional		ÒmmyyÓ or ÒyymmÓ			Date Format [optional]Ð tells Paystation what format the Card Expiry is in. If omitted, it will be set to YYMM format 
 * pstn_af		optional		Òdollars.centsÓ or ÒcentsÓ	Amount Format [optional] - Tells Paystation what format the Amount is in. If omitted, it will be assumed
 * 															the amount is in cents 
 * pstn_cu		optional		String value				For example New Zealand dollars would be NZD. Currency [optional] Ð the three letter
 * 															currency identifier. If not sent the default currency for the gateway is used.
 * pstn_tm		optional		Single character ÔtÕ or ÔTÕ	Test Mode [optional] - sets the Paystation server into Test Mode (for the single
 *															transaction only). It uses the merchants TEST account on the VPS server, and marks the
 *															transaction as a Test in the Paystation server. This allows the merchant to run test 
 *															transactions without incurring any costs or running live card transactions.
 * pstn_mr		optional		string value				Merchant Reference Code - a non-unique reference code which is stored against the transaction.
 * 															This is recommended because it can be used to tie the transaction to a merchants customers account, 
 *															or to tie groups of transactions to a particular ledger at the merchant. This will be seen 
 *															from Paystation Admin.
 * pstn_ct		optional		String						Card Type [optional] - the type of card used.Can contain ONE of the following.
 *																mastercard	visa	amex (if enabled)	dinersclub (if enabled)  bankcard (if enabled)  
 *															Please note that pago is not a card type, but a gateway.  
 *															CT cannot be empty, but may be omitted.
 */
		$merchantSession = $this->ID;  // FIX THIS: THE MERCHANT SESSION SHOULD BE UNIQUE VALUE FOR EACH *ATTEMPT* AT THE TRANSACITON

		$inputs['pstn_pi'] = self::$paystationMerchantID;
		$inputs['pstn_gi'] = self::$paystationGatewayID;
		$inputs['pstn_ms'] = $merchantSession;
		$inputs['pstn_2p'] = 't';
		$inputs['pstn_nr'] = 't';

		// 2) Payment Informations
		
		$inputs['pstn_am'] = $this->Amount;
		if ($this->Currency) $inputs['pstn_cu'] = $this->Currency;
		$inputs['pstn_mr'] = $this->ID;
		$inputs['pstn_af'] = 'dollars.cents';

		// 3) Credit Card Informations
		$inputs['pstn_cn'] = implode('', $data['Paystation_CreditCardNumber']);
		$inputs['pstn_ex'] = $data['Paystation_CreditCardExpiry'];
		$inputs['pstn_df'] = 'mmyy';

		if (self::$test_mode) {
			// Force test values, provided by paystation for testing.
			$inputs['pstn_cn'] = '5123456789012346';
			$inputs['pstn_ex'] = '0513';
			$inputs['pstn_am'] = '1.00';
			$inputs['pstn_tm'] = 't';
		}
		
		// 4) Paystation Transaction Sending
			
  		$responseFields = $this->doPayment($inputs);

  		// 5) Paystation Response Management

		if($responseFields['errorCode'] == 0) {
			$this->Status = 'Success';
			$result = new Payment_Success();
		}
		else {
			$this->Status = 'Failure';
			$result = new Payment_Failure();
		}

		if($helpText = $responseFields['message']) $this->Message = $helpText;
		if (isset($responseFields['TransactionID'])) $this->TxnNo = $responseFields['TransactionID'];
		if (isset($responseFields['ReturnReceiptNumber'])) $this->ReceiptNo = $responseFields['ReturnReceiptNumber'];

		$this->write();
		return $result;
	}

	function doPayment(array $inputs) {
		
		// 1) Set-up post parameters

		$params = "";
		$sep = "";
		foreach ($inputs as $key => $value) {
			$params .= "{$sep}{$key}={$value}";
			$sep = "&";
		}

//		Debug::show("URL is " . $params);

		// 2) CURL Creation
		
		$clientURL = curl_init(); 
		curl_setopt($clientURL, CURLOPT_URL, self::$url);
		curl_setopt($clientURL, CURLOPT_POST, 1);
		curl_setopt($clientURL, CURLOPT_POSTFIELDS, $params);
		curl_setopt($clientURL, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($clientURL, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		curl_setopt($clientURL, CURLOPT_SSLVERSION, 3);
		curl_setopt($clientURL, CURLOPT_SSL_VERIFYHOST,  2);

		// This option shouldn't be turned off, but we're getting a certificate error, so this bypasses it.
		curl_setopt($clientURL, CURLOPT_SSL_VERIFYPEER, false);

		// 3) CURL Execution
		
		$resultXml = curl_exec($clientURL);

// test:		$resultXml = '<?xml version="1.0" standalone="yes"? ><response><ec>4</ec><em>Transaction not approved</em></response>';
		// Debug::show("raw result from url is: " . $resultXml);

		if (!$resultXml) Debug::show("curl error:" . curl_error($clientURL));

		// 4) CURL Closing
		
		curl_close ($clientURL);
		
		// 5) XML Parser Creation
		$xmlParser = xml_parser_create();
		$values = null;
		$indexes = null;
		xml_parse_into_struct($xmlParser, $resultXml, $values, $indexes);
		xml_parser_free($xmlParser);

		// 6) XML Result Parsed In A PHP Array
		// We expect XML that looks like this:
		// <?xml version="1.0" standalone="yes"? > 
		// <response> 
		//   <ec>0</ec> 
		//   <em>Transaction approved</em> 
		// </response>
		$result = array();
		$map = array("EC" => "errorCode", "EM" => "message", "ReturnReceiptNumber" => "ReturnReceiptNumber", "TransactionID" => "TransactionID");
		foreach ($values as $xmlElement) {
			if (isset($map[$xmlElement['tag']]))
				$result[$map[$xmlElement['tag']]] = $xmlElement['value'];
		}

		return $result;
	}
}

?>