<?php

/**
 * Sub-class of Payment that supports Worldpay as its payment processor
 *  
 * Configuration
 * =============
 * You need to define the installation ID, test mode and callback
 * password in _config.php of your project:
 * WorldpayPayment::set_installation_id(111111);
 * WorldpayPayment::set_testmode(100);
 * WorldpayPayment::set_callback_password(blahblah);
 * 
 * @package payment
 */
class WorldpayPayment extends Payment {
	
	// WorldPay Informations
	
	protected static $privacy_link = 'http://www.worldpay.com/about_us/index.php?page=privacy';

	protected static $logo = 'payment/images/payments/worldpay.gif';
	
	// URL

	protected static $url = 'https://select.worldpay.com/wcc/purchase';
	
	protected static $test_url = 'https://select-test.worldpay.com/wcc/purchase';
	
	// Test Mode

	protected static $test_mode = false;
	
	protected static $test_mode_name;
	
	protected static function set_test_mode($name) {
		self::$test_mode = '100';
		self::$test_mode_name = $name;
	}
	
	static function set_test_mode_refused_transaction() {
		self::set_test_mode('REFUSED');
	}
	
	static function set_test_mode_authorised_transaction() {
		self::set_test_mode('AUTHORISED');
	}
	
	static function set_test_mode_error_transaction() {
		self::set_test_mode('ERROR');
	}
	
	static function set_test_mode_captured_transaction() {
		self::set_test_mode('CAPTURED');
	}
	
	// Payment Informations
	
	protected static $installation_id;
	
	static function set_installation_id($installation_id) {
		self::$installation_id = $installation_id;
	}
	
	protected static $result_file;
	
	static function set_result_file($result_file) {
		self::$result_file = $result_file;
	}
	
	protected static $merchant_code;
	
	static function set_merchant_code($merchant_code) {
		self::$merchant_code = $merchant_code;
	}
	
	protected static $authorisation_mode;
	
	static function set_authorisation_mode_full() {
		self::$authorisation_mode = 'A';
	}
	
	static function set_authorisation_mode_pre() {
		self::$authorisation_mode = 'E';
	}
	
	static function set_authorisation_mode_post() {
		self::$authorisation_mode = 'O';
	}
	
	protected static $authorisation_valid_from;
	
	static function set_authorisation_valid_from($authorisation_valid_from) {
		self::$authorisation_valid_from = $authorisation_valid_from;
	}
	
	protected static $authorisation_valid_to;
	
	static function set_authorisation_valid_to($authorisation_valid_to) {
		self::$authorisation_valid_to = $authorisation_valid_to;
	}
		
	static $callback_password;
		static function set_callback_password($pass) {
		self::$callback_password = $pass;
	}
	
	function getPaymentFormFields() {
		$logo = '<img src="' . self::$logo . '" alt="Credit card payments powered by WorldPay"/>';
		$privacyLink = '<a href="' . self::$privacy_link . '" target="_blank" title="Read WorldPay\'s privacy policy">' . $logo . '</a><br/>';
		return new FieldSet(
			new LiteralField('WorldPayInfo', $privacyLink),
			new LiteralField(
				'WorldPayPaymentsList',
				'<img src="payment/images/payments/methods/visa.jpg" alt="Visa"/>' .
				'<img src="payment/images/payments/methods/mastercard.jpg" alt="MasterCard"/>' .
				'<img src="payment/images/payments/methods/american-express.gif" alt="American Express"/>' .
				'<img src="payment/images/payments/methods/dinners-club.jpg" alt="Dinners Club"/>' .
				'<img src="payment/images/payments/methods/jcb.jpg" alt="JCB"/>'
			)
		);
	}
	
	function getPaymentFormRequirements() {
		return null;
	}
	
	function processPayment($data, $form) {
		$page = new Page();
		
		$page->Title = 'Redirection to WorldPay...';
		$page->Logo = '<img src="' . self::$logo . '" alt="Payments powered by WorldPay"/>';
		$page->Form = $this->WorldPayForm();

		$controller = new Page_Controller($page);
		
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		
		$form = $controller->renderWith('PaymentProcessingPage');
		
		return new Payment_Processing($form);
	}
	
	function WorldPayForm() {
		
		// 1) Main Informations

		$order = $this->Order();
		$items = $order->Items();
		$member = $order->Member();
		
		// 2) Mandatory Settings
		
		$inputs['instId'] = self::$installation_id;
		$inputs['cartId'] = $order->ID;
		$inputs['amount'] = $order->Total();
		$inputs['currency'] = $this->Currency;
		
		// 3) Payment And Items Informations
		
		$inputs['MC_paymentID'] = $this->ID;
		$inputs['desc'] = 'Order made on ' . DBField::create('SSDatetime', $order->Created)->Long() . ' by ' . $member->FirstName . ' ' . $member->Surname;
		
		// 4) Test Mode And Customer Name Settings
		
		if (self::$test_mode) {
			$url = self::$test_url;
			$inputs['testMode'] = self::$test_mode;
			$inputs['name'] = self::$test_mode_name;
		}
		else {
			$url = self::$url;
			$inputs['name'] = $member->FirstName . ' ' . $member->Surname;
		}
		
		// 5) Redirection Informations
		
		$inputs['MC_callback'] = Director::absoluteBaseURL() . WorldpayPayment_Handler::complete_link();
		
		// 6) Optional Payments And Authorisation Settings
		
		if(self::$result_file) $inputs['resultFile'] = self::$result_file;
		if(self::$merchant_code) $inputs['accId'] = self::$merchant_code; // Not sure about its exact syntax
		if(self::$authorisation_mode) $inputs['authMode'] = self::$authorisation_mode;
		if(self::$authorisation_valid_from) $inputs['authValidFrom'] = self::$authorisation_valid_from;
		if(self::$authorisation_valid_to) $inputs['authValidTo'] = self::$authorisation_valid_to;
		
		// 7) Prepopulating Customer Informations
		
		$inputs['address'] = $member->Address . '&#10;' . $member->AddressLine2 . '&#10;' . $member->City;
		$inputs['country'] = $member->Country;
		$inputs['email'] = $member->Email;
		
		if($member->hasMethod('getPostCode')) $inputs['postcode'] = $member->getPostCode();
		if($member->hasMethod('getFax')) $inputs['fax'] = $member->getFax();
		//$inputs['tel'] = $member->HomePhone;
		
		// 8) Form Creation

		foreach($inputs as $name => $value) $fields .= '<input type="hidden" name="' . $name . '" value="' . Convert::raw2xml($value) . '"/>';
		
		return <<<HTML
			<form id="PaymentForm" method="post" action="$url">$fields</form>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery('#PaymentForm').submit();
				});
			</script>
HTML;
	}
}

/**
 * Handler for responses from the WorldPay site
 */
class WorldpayPayment_Handler extends Controller {
	
	static $URLSegment = 'worldpay';
	
	static function complete_link() {
		return self::$URLSegment . '/complete';
	}
	
	/**
	 * Get the Order object to modify, check security that it's the object you want to modify based
	 * off Worldpay confirmation, update the Order object to show complete and Payment object to show
	 * that it was received. Finally, send a receipt to the buyer to show these details.
	 */		
	function paid() {
		// Check if callback password is the same, otherwise fail
		if($_REQUEST['callbackPW'] == WorldpayPayment::$callback_password) {
			$paymentID = $_REQUEST['MC_paymentID'];
			if(is_numeric($paymentID)) {
				if($payment = DataObject::get_by_id('WorldpayPayment', $paymentID)) {
					if($_REQUEST['transStatus'] == "Y")	$payment->Status = 'Success';
					else $payment->Status = 'Failure';
					$payment->write();
					$payment->redirectToOrder();
				}
				else USER_ERROR("CheckoutPage::OrderConfirmed - There is no Payment object for this order object (Order ID ".$orderID.")", E_USER_WARNING);
			}	
			else USER_ERROR('CheckoutPage::OrderConfirmed - Order ID is NOT numeric', E_USER_WARNING);
		}
		else USER_ERROR("CheckoutPage::OrderConfirmed - Order error - password failed" ,E_USER_WARNING);
		return;
	}
	
	function complete() {
		$paymentID = $_REQUEST['MC_paymentID'];
		$payment = DataObject::get_by_id('WorldpayPayment', $paymentID);
		$payment->TxnRef = $_REQUEST['transId'];
		$transactionStatus = $_REQUEST['transStatus'];
		if($transactionStatus == 'Y') $payment->Status = 'Success'; // Successful
		else $payment->Status = 'Failure'; // Cancelled
		$payment->Message = $_REQUEST['rawAuthMessage'];
		$payment->write();
		$payment->redirectToOrder();
	}
	
}

?>