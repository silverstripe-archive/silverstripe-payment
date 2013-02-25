<?php

/**
 */

class DPSAdapter extends Controller{

	static $allowed_actions = array(
		'AuthForm',
		'CompleteForm',
		'RefundForm',
		'PurchaseForm',
		'DPSHostedForm',
		'DPSRecurringPayment',
		'DPSHostedRecurringForm',
		'processDPSHostedResponse',
	);

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
	
	static function get_pxpost_username(){
		return self::$pxPost_Username;
	}
	
	static function get_pxpost_password(){
		return self::$pxPost_Password;
	}
	
	static function get_pxpay_userid(){
		return self::$pxPay_Userid;
	}
	
	static function get_pxpay_key(){
		return self::$pxPay_Key;
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
	
	static function postConnect(){
		$inputs['PostUsername'] = self::$pxPost_Username;
		$inputs['PostPassword'] = self::$pxPost_Password;
		$transaction = "<Txn>";
		foreach($inputs as $name => $value) {
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
		$match = preg_match('/^\<Txn\>(.*)\<\/Txn\>$/i', $resultXml, $matches);
		return $match;
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
	
	function doDPSHostedPayment($inputs, $payment){
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