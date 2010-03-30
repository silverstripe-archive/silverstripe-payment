<?php

/**
 */

class DPSAdapter extends Controller{
	protected static $pxPost_Username;
	protected static $pxPost_Password;
	protected static $pxPay_Userid;
	protected static $pxPay_Key;
	private static $mode = 'Normal';
	private static $receipt_from;
	// DPS Informations
	
	public static $privacy_link = 'http://www.paymentexpress.com/privacypolicy.htm';
	
	public static $logo = 'payment/images/payments/dps.gif';
	
	// URLs
	
	public static $pxPost_Url = 'https://www.paymentexpress.com/pxpost.aspx';
	public static $pxPay_Url = 'https://www.paymentexpress.com/pxpay/pxaccess.aspx';
	
	public static $allowed_currencies = array(
		'CAD'=>'Canadian Dollar',
		'CHF'=>'Swiss Franc',
		'EUR'=>'Euro',
		'FRF'=>'French Franc',
		'GBP'=>'United Kingdom Pound',
		'HKD'=>'Hong Kong Dollar',
		'JPY'=>'Japanese Yen',
		'NZD'=>'New Zealand Dollar',
		'SGD'=>'Singapore Dollar',
		'USD'=>'United States Dollar',
		'ZAR'=>'Rand',
		'AUD'=>'Australian Dollar',
		'WST'=>'Samoan Tala',
		'VUV'=>'Vanuatu Vatu',
		'TOP'=>"Tongan Pa'anga",
		'SBD'=>'Solomon Islands Dollar',
		'PGK'=>'Papua New Guinea Kina',
		'MYR'=>'Malaysian Ringgit',
		'KWD'=>'Kuwaiti Dinar',
		'FJD'=>'Fiji Dollar'
	);
	
	protected static $cvn_mode = true;

	protected static $credit_cards = array(
		'Visa' => 'payment/images/payments/methods/visa.jpg',
		'MasterCard' => 'payment/images/payments/methods/mastercard.jpg',
		'American Express' => 'payment/images/payments/methods/american-express.gif',
		'Dinners Club' => 'payment/images/payments/methods/dinners-club.jpg',
		'JCB' => 'payment/images/payments/methods/jcb.jpg'
	);
	
	static function set_pxpost_account($pxPost_Username, $pxPost_Password) {
		self::$pxPost_Username = $pxPost_Username;
		self::$pxPost_Password = $pxPost_Password;
	}
	
	static function set_pxpay_account($pxPay_Userid, $pxPay_Key){
		self::$pxPay_Userid = $pxPay_Userid;
		self::$pxPay_Key = $pxPay_Key;
	}
	
	static function remove_credit_card($creditCard) {
		unset(self::$credit_cards[$creditCard]);
	}
	
	static function set_receipt_from($address){
		self::$receipt_from = $address;
	}
	
	static function get_receipt_from(){
		return self::$receipt_from;
	}
	
	static function unset_cvn_mode() {
		self::$cvn_mode = false;
	}
	
	static function set_mode($mode){
		self::$mode = $mode;
	}
	
	function getBasicBrandFields(){
		$privacyLink = '<div class="dpsLogo">
							<a href="' . self::$privacy_link . '" target="_blank" title="Read DPS\'s privacy policy">
								<img src="' . self::$logo . '" alt="Credit card payments powered by DPS"/>
							</a>
						</div>';

		$paymentsList = '';
		foreach(self::$credit_cards as $name => $image) $paymentsList .= '<img src="' . $image . '" alt="' . $name . '"/>';
		return new FieldSet(
			new LiteralField('DPSLogosContainer', '<div class="dpsLogoContainer">'),
			new LiteralField('DPSInfo', $privacyLink),
			new LiteralField('DPSPaymentsList', '<div class="dpsPaymentsList">' . $paymentsList . '</div>'),
			new LiteralField('DPSLogosContainerClose', '</div>')
		);
	}
	
	function getCreditCardFields(){
		$fields = new FieldSet(
			new TextField('CardHolderName', 'Credit Card Holder Name :'),
			new CreditCardField('CardNumber', 'Credit Card Number :'),
			new TextField('DateExpiry', 'Credit Card Expiry : (MMYY)', '', 4)
		);
		
		if(self::$cvn_mode) $fields->push(new TextField('Cvc2', 'Credit Card CVN : (3 or 4 digits)', '', 4));
		return $fields;
	}
	
	function getCreditCardRequirements(){
		$jsCode = <<<JS
			require('CardHolderName');
			require('CardNumber');
			require('DateExpiry');
JS;
		$phpCode = '
			$this->requireField("CardHolderName", $data);
			$this->requireField("CardNumber", $data);
			$this->requireField("DateExpiry", $data);
		';
		return array('js' => $jsCode, 'php' => $phpCode);
	}
	
	function getOptionalFields(){
		$fields = new FieldSet(
			new EmailField("EmailAddress", "Email Address (optional)<br /><span class=\"helptxt\">Optional email address field. Will be returned to origin site for emailing of receipts etc.</span>"),
			new TextField("MerchantReference", "Merchant Reference (optional)<br /><span class=\"helptxt\">Optional Reference to Appear on Transaction Reports Max 64 Characters</span>"),
			//new TextField("TxnData1", "Txn Data 1 (optional)<br /><span class=\"helptxt\">Optional free text</span>"),
			new TextField("TxnData2", "Txn Data 2 (optional)<br /><span class=\"helptxt\">Optional free text</span>"),
			new TextField("TxnData3", "Txn Data 3 (optional)<br /><span class=\"helptxt\">Optional free text</span>")		
		);
		return $fields;
	}
	
	function getBusinessDataFields(){
		$fields =new FieldSet(
			$money = new MoneyField('Amount', '')
		);
		$money->allowedCurrencies = self::$allowed_currencies;
		$optionalFields = $this->getOptionalFields();
		foreach($optionalFields as $optionalField){
			$fields->push($optionalField);
		}
		return $fields;
	}
	
	function getBusinessDataRequirements(){
		$jsCode = <<<JS
			require('Amount');
JS;
		$phpCode = '
			$this->requireField("Amount", $data);
		';
		return array('js' => $jsCode, 'php' => $phpCode);
	}
	
	function getFormByName($formName){
		return $this->$formName();
	}
	
	function AuthForm(){
		$fields =$this->getAuthFormFields();
		$requires = $this->getAuthFormRequirements();
		$customised = array();
		$customised[] = $requires;

		$validator = new CustomRequiredFields($customised);
		$actions = new FieldSet(
			new FormAction('doAuthPayment', "Submit")
		);
		$form = new Form(Controller::curr(), 'AuthForm', $fields, $actions, $validator);
		return $form;
	}
	
	function CompleteForm(){
		$fields =$this->getCompleteFormFields();
		$requires = $this->getCompleteFormRequirements();
		$customised = array();
		$customised[] = $requires;

		$validator = new CustomRequiredFields($customised);
		$actions = new FieldSet(
			new FormAction('doCompletePayment', "Submit")
		);
		$form = new Form(Controller::curr(), 'CompleteForm', $fields, $actions, $validator);
		return $form;
	}
	
	function PurchaseForm(){
		$fields =$this->getAuthFormFields();
		$requires = $this->getAuthFormRequirements();
		$customised = array();
		$customised[] = $requires;

		$validator = new CustomRequiredFields($customised);
		$actions = new FieldSet(
			new FormAction('doPurchasePayment', "Submit")
		);
		$form = new Form(Controller::curr(), 'PurchaseForm', $fields, $actions, $validator);
		return $form;
	}
	
	function RefundForm(){
		$fields =$this->getRefundFormFields();
		$requires = $this->getRefundFormRequirements();
		$customised = array();
		$customised[] = $requires;

		$validator = new CustomRequiredFields($customised);
		$actions = new FieldSet(
			new FormAction('doRefundPayment', "Submit")
		);
		$form = new Form(Controller::curr(), 'RefundForm', $fields, $actions, $validator);
		return $form;
	}
	
	function DPSHostedForm(){
		$fields = $this->getDPSHostedFormFields();
		$requires = $this->getDPSHostedFormRequirements();
		$customised = array();
		$customised[] = $requires;

		$validator = new CustomRequiredFields($customised);
		$actions = new FieldSet(
			new FormAction('doDPSHostedPayment', "Submit")
		);
		$form = new Form(Controller::curr(), 'DPSHostedForm', $fields, $actions, $validator);
		return $form;
	}
	
	function DPSHostedRecurringForm(){
		$fields = $this->getDPSHostedFormFields();
		$requires = $this->getDPSHostedFormRequirements();
		
		$recurringFields = $this->getRecurringInfoFields();
		foreach($recurringFields as $recurringField){
			$fields->push($recurringField);
		}
		
		$recurringRequires = $this->getRecurringInfoReuirements();
		$requires['js'] .= $recurringRequires['js'];
		$requires['php'] .= $recurringRequires['php'];
		
		$customised = array();
		$customised[] = $requires;

		$validator = new CustomRequiredFields($customised);
		$actions = new FieldSet(
			new FormAction('doDPSHostedRecurringPayment', "Submit")
		);
		$form = new Form(Controller::curr(), 'DPSHostedRecurringForm', $fields, $actions, $validator);
		return $form;
	}
	
	function MerchantHostedRecurringForm(){
		$fields = $this->getAuthFormFields();
		$requires = $this->getAuthFormRequirements();
		
		$recurringFields = $this->getRecurringInfoFields();
		foreach($recurringFields as $recurringField){
			$fields->push($recurringField);
		}
		
		$recurringRequires = $this->getRecurringInfoReuirements();
		$requires['js'] .= $recurringRequires['js'];
		$requires['php'] .= $recurringRequires['php'];
		
		$customised = array();
		$customised[] = $requires;

		$validator = new CustomRequiredFields($customised);
		$actions = new FieldSet(
			new FormAction('doMerchantHostedRecurringPayment', "Submit")
		);
		$form = new Form(Controller::curr(), 'MerchantHostedRecurringForm', $fields, $actions, $validator);
		return $form;
	}

	function getPaymentFormFields() {
		$fields = $this->getBasicBrandFields();
		$ccFields = $this->getCreditCardFields();
		foreach($ccFields as $ccField){
			$fields->push($ccField);
		}
		return $fields;
	}

	function getPaymentFormRequirements() {
		return $this->getCreditCardRequirements();
	}
	
	private function getAuthFormFields(){
		$fields =$this->getPaymentFormFields();
		$businessDataFields = $this->getBusinessDataFields();
		foreach($businessDataFields as $businessDataField){
			$fields->push($businessDataField);
		}
		return $fields;
	}

	private function getAuthFormRequirements(){
		$required = $this->getPaymentFormRequirements();
		$businessDataRequired = $this->getBusinessDataRequirements();
		$required['js'] .= $businessDataRequired['js'];
		$required['php'] .= $businessDataRequired['php'];
		return $required;
	}
	
	private function getCompleteFormFields(){
		$successAuths = DataObject::get("DPSPayment", "\"Status\" = 'Success' AND \"TxnType\" = 'Auth'");
		if($successAuths && $successAuths->count()) {
			foreach($successAuths as $auth){
				if(!$auth->CanComplete()){
					$successAuths->remove($auth);
				}else{
					$auth->MatchTitle = $auth->TxnType . " Payment #".$auth->ID." (" .$auth->Amount->Currency." ".$auth->Amount->Amount.")";
				}
			}
		}
		
		$fields = new FieldSet(
			new DropdownField("AuthPaymentID", "Authorised Payments", $successAuths->map('ID', 'MatchTitle', "Select one Authorised Payment to Complete")),
			$money = new MoneyField("Amount", "Complete Amoun / Currency")
		);
		
		$money->allowedCurrencies = self::$allowed_currencies;
		return $fields;
	}

	private function getCompleteFormRequirements(){
		$required['js'] = <<<JS
			require('AuthPaymentID');
			require('Amount');
JS;
		$required['php'] = '
			$this->requireField("AuthPaymentID", $data);
			$this->requireField("Amount", $data);
		';
		return $required;
	}
	
	private function getRefundFormFields(){
		$refundables = DataObject::get("DPSPayment", "\"Status\" = 'Success' AND (\"TxnType\" = 'Complete' OR \"TxnType\" = 'Purchase')");
		if($refundables && $refundables->count()){
			foreach($refundables as $refundable){
				$refundable->MatchTitle = $refundable->TxnType . " Payment #".$refundable->ID." (" .$refundable->Amount->Currency." ".$refundable->Amount->Amount.")";
			}
		}
		
		$fields = new FieldSet(
			new DropdownField("RefundedForID", "Authorised Payments", $refundables->map('ID', 'MatchTitle', "Select one Refundable Payment to Refund")),
			$money = new MoneyField("Amount", "Refund Amoun / Currency")
		);
		
		$money->allowedCurrencies = self::$allowed_currencies;
		return $fields;
	}
	
	private function getRefundFormRequirements(){
		$required['js'] = <<<JS
			require('RefundedForID');
			require('Amount');
JS;
		$required['php'] = '
			$this->requireField("RefundedForID", $data);
			$this->requireField("Amount", $data);
		';
		return $required;
	}
	
	private function getDPSHostedFormFields(){
		$fields = $this->getBasicBrandFields();
		$businessDataFields = $this->getBusinessDataFields();
		$merchantReferenceFields = $businessDataFields->dataFieldByName('MerchantReference');
		$merchantReferenceFields -> setTitle("Merchant Reference<br /><span class=\"helptxt\">Optional Reference to Appear on Transaction Reports Max 64 Characters</span>");
		foreach($businessDataFields as $businessDataField){
			$fields->push($businessDataField);
		}
		return $fields;
	}
	
	private function getDPSHostedFormRequirements(){
		$required = $this->getBusinessDataRequirements();
		$required['js'] .= <<<JS
require('MerchantReference');
JS;
		$required['php'] .= '
			$this->requireField("MerchantReference", $data);
		';
		return $required;
	}
	
	private function getRecurringInfoFields(){
		$fields = new FieldSet(
			new DropdownField('Frequency', "Frequency", array(""=>"Select your payment frequency", 'Weekly'=>'Weekly', 'Monthly'=>'Monthly', 'Yearly'=>'Yearly')),
			$stardingDate = new DateField("StartingDate", "Starting Date"),
			new NumericField("Times", "Recurring Times (if leave it blank, the payment will be set to pay without limitation of recurring counts)")
		);
		$stardingDate->setConfig('dmyfields', true);
		return $fields;
	}
	
	private function getRecurringInfoReuirements(){
		$required['js'] = <<<JS
			require('Frequency');
			require('StartingDate');
JS;
		$required['php'] = '
			$this->requireField("Frequency", $data);
			$this->requireField("StartingDate", $data);
		';
		return $required;
	}
	
	//Payment Function
	function doPayment($inputs, $payment) {
		if(self::$mode === 'Error_Handling_Mode'){
			$this->tsixe_dluoc_reven_taht_noitcnuf_a_function_that_never_could_exist();
		}
		// 1) Main Settings
		//$inputs = array();
		$inputs['PostUsername'] = self::$pxPost_Username;
		$inputs['PostPassword'] = self::$pxPost_Password;
		//$inputs = array();
		
		// 1) Transaction Creation
		$transaction = "<Txn>";
		foreach($inputs as $name => $value) {
			if($name == "Amount") {
				$value = number_format($value, 2, '.', '');
			}
			$transaction .= "<$name>$value</$name>";
		}
		$transaction .= "</Txn>";
		
		// 2) CURL Creation
		$clientURL = curl_init(); 
		curl_setopt($clientURL, CURLOPT_URL, self::$pxPost_Url);
		curl_setopt($clientURL, CURLOPT_POST, 1);
		curl_setopt($clientURL, CURLOPT_POSTFIELDS, $transaction);
		curl_setopt($clientURL, CURLOPT_RETURNTRANSFER, 1);
		//curl_setopt($clientURL, CURLOPT_SSL_VERIFYPEER, 0); //Needs to be included if no *.crt is available to verify SSL certificates
		curl_setopt($clientURL, CURLOPT_SSLVERSION, 3);
		
		// 3) CURL Execution
		
		$resultXml = curl_exec($clientURL);
		$payment->ResponseXML = $resultXml;
		// 4) CURL Closing
		curl_close ($clientURL);

		// 5) XML Parser Creation
		$xmlParser = xml_parser_create();
		$values = null;
		$indexes = null;
		xml_parse_into_struct($xmlParser, $resultXml, $values, $indexes);
		xml_parser_free($xmlParser);
		
		// 6) XML Result Parsed In A PHP Array
		$resultPhp = array();
		$level = array();
		foreach($values as $xmlElement) {
			if($xmlElement['type'] == 'open') {
				if(array_key_exists('attributes', $xmlElement)) list($level[$xmlElement['level']], $extra) = array_values($xmlElement['attributes']);
				else $level[$xmlElement['level']] = $xmlElement['tag'];
			}
			else if ($xmlElement['type'] == 'complete') {
				$startLevel = 1;
				$phpArray = '$resultPhp';
				while($startLevel < $xmlElement['level']) $phpArray .= '[$level['. $startLevel++ .']]';
				$phpArray .= '[$xmlElement[\'tag\']] = array_key_exists(\'value\', $xmlElement)? $xmlElement[\'value\'] : null;';
				eval($phpArray);
			}
		}
		
		$responseFields = $resultPhp['TXN'];

		// 7) DPS Response Management
		if($responseFields['SUCCESS']) {
			$payment->Status = 'Success';
			if($authcode = $responseFields['1']['AUTHCODE']) $payment->AuthCode = $authcode;
			if($dpsBillingID = $responseFields['1']['DPSBILLINGID']) $payment->DPSBillingID = $dpsBillingID;
			
			$dateSettlement = $responseFields['1']['DATESETTLEMENT'];
			$payment->SettlementDate = substr($dateSettlement, 0, 4) ."-".substr($dateSettlement, 4, 2)."-".substr($dateSettlement, 6, 2);
			$payment->Amount->Currency = $responseFields['1']['INPUTCURRENCYNAME'];
		}
		else {
			$payment->Status = 'Failure';
		}
		if($transactionRef = $responseFields['DPSTXNREF']) $payment->TxnRef = $transactionRef;
		if($helpText = $responseFields['HELPTEXT']) $payment->Message = $helpText;
		else if($responseText = $responseFields['RESPONSETEXT']) $payment->Message = $responseText;

		$payment->write();
		
		if(self::$mode === 'Rolling_Back_Mode'){
			DB::getConn()->transactionRollback();
		}
	}
	
	function doDPSHosedPayment($inputs, $payment){
		$request = new PxPayRequest();
		foreach($inputs as $element => $value){
			$funcName = 'set'.$element;
			$request->$funcName($value);
		}

		// submit payment request to get the URL for redirection
		$pxpay = new PxPay(self::$pxPay_Url, self::$pxPay_Userid, self::$pxPay_Key);
		$request_string = $pxpay->makeRequest($request);
		$response = new MifMessage($request_string);
		$valid = $response->get_attribute("valid");
		if($valid){
			// MifMessage was clobbering ampersands on some environments; SimpleXMLElement is more robust
	        $xml = new SimpleXMLElement($request_string);
	        $urls = $xml->xpath('//URI');     
	        $url = $urls[0].'';
			DB::getConn()->endTransaction();
			if(self::$mode == "Unit_Test_Only"){
				return $url;
			}else{
		        header("Location: ".$url);
				die;
			}
		}else{
			$payment->Message = "Invalid Request String";
			$payment->write();
		}
	}
	
	function processDPSHostedResponse(){
		if(preg_match('/^PXHOST/i', $_SERVER['HTTP_USER_AGENT'])){
			$dpsDirectlyConnecting = 1;
		}
	
		$pxpay = new PxPay(self::$pxPay_Url, self::$pxPay_Userid, self::$pxPay_Key);

		$enc_hex = $_REQUEST["result"];

		$rsp = $pxpay->getResponse($enc_hex);

		if(isset($dpsDirectlyConnecting) && $dpsDirectlyConnecting) {
			// DPS Service connecting directly
			$success = $rsp->getSuccess();   # =1 when request succeeds
			echo ($success =='1') ? "success" : "failure";
		} else {
			// Human visitor
			$paymentID = $rsp->getTxnData1();
			$SQL_paymentID = (int)$paymentID;

			if($dpsBillingID = $rsp->getDpsBillingId()){
				$payment = DataObject::get_by_id('DPSRecurringPayment', $SQL_paymentID);
				$payment->DPSBillingID = $dpsBillingID;
			}else{
				$payment = DataObject::get_by_id("DPSPayment", $SQL_paymentID);
			}
		
			if($payment) {
				DB::getConn()->startTransaction();
				try{
					$payment->ResponseXML = $rsp->toXml();
					$success = $rsp->getSuccess();
					if($success =='1'){
						// @todo Use AmountSettlement for amount setting?
						$payment->TxnRef = $rsp->getDpsTxnRef();
						$payment->AuthCode = $rsp->getAuthCode();
						$payment->Status="Success";
					} else {
						$payment->Status="Failure";
					}
					$payment->Message=$rsp->getResponseText();
					$payment->write();
					DB::getConn()->endTransaction();
				}catch(Exception $e){
					DB::getConn()->transactionRollback();
					$payment->handleError($e);
				}
				Director::redirect($payment->DPSHostedRedirectURL);
			}
		}
	}
}