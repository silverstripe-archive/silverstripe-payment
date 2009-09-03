<?php

/**
 * Implementation of Payment that supports PayPal Website Payment Standard 
 * (https://www.paypal.com/IntegrationCenter/ic_standard_home.html) as its payment processor
 * 
 * Configure using PayPalPayment::setMyVariable(value); 
 * in www.mysite.com/ecommerce/_config.php file
 * 
 * Must configure:
 * 
 * $setPayPalRealAccount;
 * $setPayPalTestAccount;
 * $setPayPalUseTestAccount;
 * 
 * Optionally configure:
 * 
 * $setPayPalImageLocation;
 * $setPayPalContinueNextButton;
 * $setPayPalPurchaseName;
 * $setPayPalCppHeaderImage;
 * $setPayPalCppHeaderBackcolor;
 * $setPayPalCppHeaderBordercolor;
 * $setPayPalCppPayflowColor;
 * $setPayPalCs;
 * REQUIREMENTS:
 * 
 * Need to add: "PayPalInstructions" field to CheckoutPage.php
 * 
 * static $db = array(
 *   "PayPalInstructions" => "HTMLText"
 * );
 * 
 * Have a PayPalPaymentPage.ss template or replace some code below
 * (search for renderWith). On the PayPalPaymentPage you can use
 * $PayPalInstructions (see above)
 * 
 * @package payment 
 */
class PayPalPayment extends Payment {
	static $db = array(
		'AuthorisationCode' => 'Text'
	);
	
	// PayPal Informations
	
	protected static $privacy_link = 'https://www.paypal.com/us/cgi-bin/webscr?cmd=p/gen/ua/policy_privacy-outside';
	
	protected static $logo = 'payment/images/payments/paypal.jpg';
	
	// URLs

	protected static $url = 'https://www.paypal.com/cgi-bin/webscr';
	
	protected static $test_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';

	// Test Mode

	protected static $test_mode = false;
	
	protected static $test_account_email;
	
	static function set_test_mode($test_account_email) {
		self::$test_mode = true;
		self::$test_account_email = $test_account_email;
	}

	// Payment Informations

	protected static $account_email;
	
	static function set_account_email($account_email) {self::$account_email = $account_email;}

	// PayPal Pages Style Optional Informations

	protected static $continue_button_text;
	
	static function set_continue_button_text($continue_button_text) {self::$continue_button_text = $continue_button_text;}

	protected static $header_image_url;
	
	static function set_header_image_url($header_image_url) {self::$header_image_url = $header_image_url;}

	protected static $header_back_color;
	
	static function set_header_back_color($header_back_color) {self::$header_back_color = $header_back_color;}

	protected static $header_border_color;
	
	static function set_header_border_color($header_border_color) {self::$header_border_color = $header_border_color;}

	protected static $payflow_color;
	
	static function set_payflow_color($payflow_color) {self::$payflow_color = $payflow_color;}

	protected static $back_color;
	
	static function set_back_color_black() {self::$back_color = '1';}

	protected static $image_url;
	
	static function set_image_url($image_url) {self::$image_url = $image_url;}

	protected static $page_style;
	
	static function set_page_style($page_style) {self::$page_style = $page_style;}
	
	function getPaymentFormFields() {
		$logo = '<img src="' . self::$logo . '" alt="Credit card payments powered by PayPal"/>';
		$privacyLink = '<a href="' . self::$privacy_link . '" target="_blank" title="Read PayPal\'s privacy policy">' . $logo . '</a><br/>';
		return new FieldSet(
			new LiteralField('PayPalInfo', $privacyLink),
			new LiteralField(
				'PayPalPaymentsList',
				'<img src="payment/images/payments/methods/visa.jpg" alt="Visa"/>' .
				'<img src="payment/images/payments/methods/mastercard.jpg" alt="MasterCard"/>' .
				'<img src="payment/images/payments/methods/american-express.gif" alt="American Express"/>' .
				'<img src="payment/images/payments/methods/discover.jpg" alt="Discover"/>' .
				'<img src="payment/images/payments/methods/paypal.jpg" alt="PayPal"/>'
			)
		);
	}

	function getPaymentFormRequirements() {return null;}

	function processPayment($data, $form) {
		$page = new Page();

		$page->Title = 'Redirection to PayPal...';
		$page->Logo = '<img src="' . self::$logo . '" alt="Payments powered by PayPal"/>';
		$page->Form = $this->PayPalForm();

		$controller = new Page_Controller($page);
		
		$form = $controller->renderWith('PaymentProcessingPage');

		return new Payment_Processing($form);
	}

	function PayPalForm() {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		
		// 1) Main Informations
		$fields = '';
		$order = $this->Order();
		$items = $order->Items();
		$member = $order->Member();
		
		// 2) Main Settings
		
		$url = self::$test_mode ? self::$test_url : self::$url;
		$inputs['cmd'] = '_cart';
		$inputs['upload'] = '1';
		
		// 3) Items Informations
		
		$cpt = 0;
		foreach($items as $item) {
			$inputs['item_name_' . ++$cpt] = $item->TableTitle();
			// item_number is unnecessary
			$inputs['amount_' . $cpt] = $item->UnitPrice();
			$inputs['quantity_' . $cpt] = $item->Quantity;
		}

		// 4) Payment Informations And Authorisation Code

		$inputs['business'] = self::$test_mode ? self::$test_account_email : self::$account_email;
		$inputs['custom'] = $this->ID . '-' . $this->AuthorisationCode; 
		// Add Here The Shipping And/Or Taxes
		$inputs['currency_code'] = $this->Currency;

		// 5) Redirection Informations

		$inputs['cancel_return'] = Director::absoluteBaseURL() . PayPalPayment_Handler::cancel_link($inputs['custom']);
		$inputs['return'] = Director::absoluteBaseURL() . PayPalPayment_Handler::complete_link();
		$inputs['rm'] = '2';
		// Add Here The Notify URL

		// 6) PayPal Pages Style Optional Informations

		if(self:: $continue_button_text) $inputs['cbt'] = self::$continue_button_text;

		if(self::$header_image_url) $inputs['cpp_header_image'] = urlencode(self::$header_image_url);
		if(self::$header_back_color) $inputs['cpp_headerback_color'] = self::$header_back_color;
		if(self::$header_border_color) $inputs['cpp_headerborder_color'] = self::$header_border_color;
		if(self::$payflow_color) $inputs['cpp_payflow_color'] = self::$payflow_color;
		if(self::$back_color) $inputs['cs'] = self::$back_color;
		if(self::$image_url) $inputs['image_url'] = urlencode(self::$image_url);
		if(self::$page_style) $inputs['page_style'] = self::$page_style;

		// 7) Prepopulating Customer Informations

		$inputs['first_name'] = $member->FirstName;
		$inputs['last_name'] = $member->Surname;
		$inputs['address1'] = $member->Address;
		$inputs['address2'] = $member->AddressLine2;
		$inputs['city'] = $member->City;
		$inputs['country'] = $member->Country;
		$inputs['email'] = $member->Email;

		if($member->hasMethod('getState'))	$inputs['state'] = $member->getState();
		if($member->hasMethod('getZip')) $inputs['zip'] = $member->getZip();

		// 8) Form Creation
		if(is_array($inputs) && count($inputs)) { 
			foreach($inputs as $name => $value) {
				$ATT_value = Convert::raw2att($value);
				$fields .= "<input type=\"hidden\" name=\"$name\" value=\"$ATT_value\" />";
			}
		}

		return <<<HTML
			<form id="PaymentForm" method="post" action="$url">
				$fields
				<input type="submit" value="Submit" />
			</form>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery("input[type='submit']").hide();
					jQuery('#PaymentForm').submit();
				});
			</script>
HTML;
	}
}

/**
 * Handler for responses from the PayPal site
 */
class PayPalPayment_Handler extends Controller {

	static $URLSegment = 'paypal';
	
	static function complete_link() {
		return self::$URLSegment . '/complete';
	}
	
	static function cancel_link($custom) {
		return self::complete_link() . '?custom=' . $custom;
	}

	/**
	 * Manages the 'return' and 'cancel' PayPal replies
	 * To-do : Escape as SQL : SQL_data
	 */
	function complete() {
		if(isset($_REQUEST['custom']) && $custom = $_REQUEST['custom']) {
			$params = explode('-', $custom);
			if(count($params) == 2) {
				if($payment = DataObject::get_by_id('PayPalPayment', $params[0])) {
					if($payment->AuthorisationCode == $params[1]) {
						if(isset($_REQUEST['payment_status']) && $_REQUEST['payment_status'] == 'Completed') {
							$payment->Status = 'Success';
							$payment->TxnRef = $_REQUEST['txn_id'];
						}
						else $payment->Status = 'Failure';
						
						$payment->write();
						
						$payment->redirectToOrder();
					}
					else {
						user_error('error 1');
					}
				}
				else {
					user_error('error 2');
				}
			}
			else {
				user_error('error 3');
			}
		}
		else {
			user_error('error 4');
		}
	}
}

/* what comes back from Paypal as get variables (see https://www.paypal.com/IntegrationCenter/ic_ipn-pdt-variable-reference.html)
 txn_type=web_accept
 payment_date=19%3A09%3A25+Apr+15%2C+2008+PDT
 last_name=Tester
 residence_country=US
 item_name=templates
 payment_gross=70.00
 mc_currency=USD
 business=abc%40def.ghi (email of business owner)
 payment_type=instant
 payer_status=verified
 verify_sign=AFhW6IfTc.P96fDJUr48Gahf6BlHAhxUUkTH59p-rGnYUe2n5es5ZeLn
 test_ipn=1
 payer_email=testpayer%40abc.def.ghi (person paying)
 tax=0.00
 txn_id=40863971JS8149811
 first_name=Chester
 receiver_email=aaa%40bbb.com
 quantity=1
 payer_id=QW5PPSM3TFGPA
 receiver_id=LZZDEWP3XWLUG
 item_number=39
 payment_status=Completed
 mc_fee=3.03
 payment_fee=3.03
 shipping=0.00
 mc_gross=70.00
 custom=39
*/
?>