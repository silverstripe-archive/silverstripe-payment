<?php

class PayPalAdapter extends Controller{
	function getFormByName($formName){
		return $this->$formName();
	}
	
	function getPayPalFormFields(){
		$fields = $this->getBasicBrandFields();
	}
	
	function getPayPalFormRequirements(){
		
	}
	
	function PayPalForm() {
		$fields =$this->getPayPalFormFields();
		$requires = $this->getPayPalFormRequirements();
		$customised = array();
		$customised[] = $requires;

		$validator = new CustomRequiredFields($customised);
		$actions = new FieldSet(
			new FormAction('doAuthPayment', "Submit")
		);
		$form = new Form(Controller::curr(), 'AuthForm', $fields, $actions, $validator);
		return $form;
		
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