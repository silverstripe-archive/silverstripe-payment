<?php

class PaystationHostedPayment extends Payment {
	
	// Paystation Information
	
	protected static $privacy_link = 'http://paystation.co.nz/privacy-policy';
	
	protected static $logo = 'payment/images/payments/paystation.jpg';
	
	// URLs

	protected static $url = 'https://www.paystation.co.nz/dart/darthttp.dll?paystation&pi=%s&ms=%s&am=%s';

	// Test Mode

	protected static $test_mode = false;
	
	static function set_test_mode() {
		self::$test_mode = true;
	}
	
	// Payment Informations

	protected static $merchant_id;
	
	static function set_merchant_id($merchant_id) {
		self::$merchant_id = $merchant_id;
	}
	
	protected static $merchant_ref;
	
	static function set_merchant_ref($merchant_ref) {
		self::$merchant_ref = $merchant_ref;
	}
	
	function getPaymentFormFields() {
		$logo = '<img src="' . self::$logo . '" alt="Credit card payments powered by Paystation"/>';
		$privacyLink = '<a href="' . self::$privacy_link . '" target="_blank" title="Read Paystation\'s privacy policy">' . $logo . '</a><br/>';
		return new FieldSet(
			new LiteralField('PaystationInfo', $privacyLink),
			new LiteralField(
				'PaystationPaymentsList',
				'<img src="payment/images/payments/methods/visa.jpg" alt="Visa"/>' .
				'<img src="payment/images/payments/methods/mastercard.jpg" alt="MasterCard"/>' .
				'<img src="payment/images/payments/methods/american-express.gif" alt="American Express"/>'
			)
		);
	}
	
	function getPaymentFormRequirements() {
		return null;
	}
	
	function processPayment($data, $form) {
		$page = new Page();

		$page->Title = 'Redirection to Paystation...';
		$page->Logo = '<img src="' . self::$logo . '" alt="Payments powered by Paystation"/>';
		$page->Form = $this->PaystationForm();
		
		$controller = new Page_Controller($page);
		
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		
		$form = $controller->renderWith('PaymentProcessingPage');
		
		return new Payment_Processing($form);
	}

	function PaystationForm() {
		
		// 1) Main Informations
		
		$order = $this->Order();
		$member = $order->Member();
		
		// 2) Main Settings
		
		$amount = $order->Total() * 100;
		$url = sprintf(self::$url, self::$merchant_id, $this->ID, $amount);
		if(self::$test_mode) $url .= '&tm=t';
		if(self::$merchant_ref) $url .= '&merchant_ref=' . self::$merchant_ref;
		$url = Convert::raw2xml($url);
		
		// 3) Form Creation
		
		return<<<HTML
			<script type="text/javascript">
				jQuery(document).ready(function() {
					location = "$url";
				});
			</script>
HTML;
	}
}

/**
 * Handler for responses from the PayPal site
 */
class PaystationHostedPayment_Handler extends Controller {

	static $URLSegment = 'paystation';
	
	static function complete_link() {
		return self::$URLSegment . '/complete';
	}
	
	function complete() {
		if(isset($_REQUEST['ec'])) {
			if(isset($_REQUEST['ms'])) {
				if($payment = DataObject::get_by_id('PaystationHostedPayment', $_REQUEST['ms'])) {
					$payment->Status = $_REQUEST['ec'] == '0' ? 'Success' : 'Failure';
					if($_REQUEST['ti']) $payment->TxnRef = $_REQUEST['ti'];
					if($_REQUEST['em']) $payment->Message = $_REQUEST['em'];
					
					$payment->write();
						
					$payment->redirectToOrder();
				}
				else user_error('There is no any Paystation hosted payment which ID is #' . $_REQUEST['ms'], E_USER_ERROR);
			}
			else user_error('There is no any Paystation hosted payment ID specified', E_USER_ERROR);
		}
		else user_error('There is no any Paystation hosted payment error code specified', E_USER_ERROR);
	}
	
}