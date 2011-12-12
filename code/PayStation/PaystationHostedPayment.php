<?php

class PaystationHostedPayment extends Payment {
  
  static $db = array(
		'TransactionID' => 'Varchar'
	);

	protected static $privacy_link = 'http://paystation.co.nz/privacy-policy';
	protected static $logo = 'payment/images/paystation.jpg';
	protected static $url = 'https://www.paystation.co.nz/direct/paystation.dll';
	protected static $test_mode = false;
	protected static $paystation_id;
	protected static $gateway_id;
	
	/**
	 * Site description can be used to differentiate transactions from different websites in Paystation admin.
	 * 
	 * @var String
	 */
	protected static $site_description;
	
	/**
	 * Merchant reference is optional, but is a great way to tie a transaction in with a customer 
	 * (this is displayed in Paystation Administration when looking at transaction details). 
	 * 
	 * @var String Max length is 64 characters
	 */
	protected static $merchant_ref;
	
	static function set_test_mode() {
		self::$test_mode = true;
	}
	
  static function set_paystation_id($id) {
		self::$paystation_id = $id;
	}
	
  static function set_gateway_id($id) {
		self::$gateway_id = $id;
	}
	
  static function set_site_description($description) {
		self::$site_description = $description;
	}
	
  static function set_merchant_reference($ref) {
		self::$merchant_ref = urlencode($ref);
	}
	
	function getPaymentFormFields() {
		$logo = '<img src="' . self::$logo . '" alt="Credit card payments powered by Paystation"/>';
		$privacyLink = '<a href="' . self::$privacy_link . '" target="_blank" title="Read Paystation\'s privacy policy">' . $logo . '</a><br/>';
		return new FieldSet(
			new LiteralField('PaystationInfo', $privacyLink),
			new LiteralField(
				'PaystationPaymentsList',

				'<div id="VisaCard" class="CreditCard"></div>' .
  			'<div id="MasterCard" class="CreditCard"></div>' .
  			'<div id="AmEx" class="CreditCard"></div>'.
			
			  '<a href="' . self::$privacy_link . '" target="_blank" rel="nofollow" class="PrivacyPolicyLink" title="PayStation privacy policy">PayStation Privacy Policy</a>'
			)
		);
	}
	
	function getPaymentFormRequirements() {
		return null;
	}
	
	function processPayment($data, $form) {

	  $paystationURL	= self::$url;
    $amount		= $data['Amount'] * 100;
    $pstn_pi	= self::$paystation_id; 
    $pstn_gi	= self::$gateway_id; 
    $site = self::$site_description;
    $pstn_mr = self::$merchant_ref;
    $testMode = self::$test_mode;
  	$merchantSession	= urlencode($site.'-'.time().'-'.$this->makePaystationSessionID(8,8)); //max length of ms is 64 char 
  	
  	//Create URL to initiate transation with PayStation
    $paystationParams = "paystation&pstn_pi=".$pstn_pi.
    										"&pstn_gi=".$pstn_gi.
    										"&pstn_ms=".$merchantSession.
    										"&pstn_am=".$amount.
    										"&pstn_mr=".$pstn_mr.
    										"&pstn_nr=t";
    
    if ($testMode) $paystationParams = $paystationParams."&pstn_tm=t";
    
    
    //Do Transaction Initiation POST
    $initiationResult=$this->directTransaction($paystationURL, $paystationParams);
    $p = xml_parser_create();
    xml_parse_into_struct($p, $initiationResult, $vals, $tags);
    xml_parser_free($p);
    
    //Analyze the resulting XML from Transaction Initiation POST
  	for ($j=0; $j < count($vals); $j++) {
  	  
  	  //Get URL to redirect to on PayStation site in order to pay for this order
    	if (strcasecmp($vals[$j]["tag"], "DIGITALORDER") == 0 && isset($vals[$j]["value"])){
    		$digitalOrder = $vals[$j]["value"];
    	}
    	
    	//Get Paystation Transaction ID for reference
    	if (strcasecmp($vals[$j]["tag"], "PAYSTATIONTRANSACTIONID") == 0 && isset($vals[$j]["value"])){
    		$paystationTransactionID = $vals[$j]["value"];
    	}
    	
    	//Get Paystation Error message
  	  if (strcasecmp($vals[$j]["tag"], "PAYSTATIONERRORMESSAGE") == 0 && isset($vals[$j]["value"])){
    		$paystationErrorMessage = $vals[$j]["value"];
    	}
    }
    
    //If URL to pay for this Order is returned, redirect customer to payment gateway
	  if (isset($digitalOrder) && $digitalOrder) {
	    
	    $this->TransactionID = (isset($paystationTransactionID)) ? $paystationTransactionID : null;
  	  $this->Status = "Pending";
  		$this->write();
  		
			Director::redirect($digitalOrder);
			return new Payment_Processing();
    } 
    else {
      
      $this->TransactionID = (isset($paystationTransactionID)) ? $paystationTransactionID : null;
      $this->Message = (isset($paystationErrorMessage)) ? "PayStation responded with the error: $paystationErrorMessage" : "PayStation could not be contacted";
  		$this->Status = 'Failure';
  		$this->write();
  		
  		return new Payment_Failure($this->Message);
    }
	}
	
	private function directTransaction($url,$params){

  	$userAgent = null;
  	
  	if (isset($_SERVER['HTTP_USER_AGENT'])) {
  	  $userAgent = $_SERVER['HTTP_USER_AGENT'];
  	}
  	
  	$defined_vars = get_defined_vars();
  	if (isset($defined_vars['HTTP_USER_AGENT'])) {
  	  $userAgent = $defined_vars['HTTP_USER_AGENT'];
  	}

  	//use curl to get reponse	
  	$ch = curl_init();
  	curl_setopt($ch, CURLOPT_POST,1);
  	curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
  	curl_setopt($ch, CURLOPT_URL,$url);
  	
  	//Curl error: SSL certificate problem, verify that the CA cert is OK.
  	//OpenSSL not installed correctly
  	//http://ademar.name/blog/2006/04/curl-ssl-certificate-problem-v.html
  	//Need to make sure it is working correctly on the server
  	//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2);
  	
  	curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0); 
  	
  	curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
  	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
  	$result=curl_exec ($ch);
  	
  	
  	if (curl_errno($ch)) {
  	  echo $url . '<br />';
  	  echo $params . '<br />';
  	  
  	  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  	  echo 'HTTP Code: ' . $httpcode . '<br />';
      echo 'Curl error: ' . curl_error($ch) . '<br />';
    }

    curl_close ($ch);
  
  	return $result;	
  }
	
	private function makePaystationSessionID ($min=8, $max=8) {

    # seed the random number generator - straight from PHP manual
    $seed = (double)microtime()*getrandmax();
    srand($seed);
  
    # make a string of $max characters with ASCII values of 40-122
    $pass = '';
    $p=0; while ($p < $max):
      $r=123-(rand()%75);
      $pass.=chr($r);
    $p++; endwhile;
  
    # get rid of all non-alphanumeric characters
    $pass=preg_replace("/[^a-zA-NP-Z1-9]+/","",$pass);
  
    # if string is too short, remake it
    if (strlen($pass)<$min):
      $pass=$this->makePaystationSessionID($min,$max);
    endif;
  
    return $pass;
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
	  
	  //Check for ti - TransactionID
	  //Get payment from TI
	  
	  $request = $this->getRequest();
	  $transactionID = $request->getVar('ti');
	  $errorCode = $request->getVar('ec');
	  $errorMessage = $request->getVar('em');
	  
	  if ($transactionID) {
	    
	    $payment = DataObject::get('PaystationHostedPayment', 'TransactionID = ' . Convert::raw2sql($transactionID));
	    
	    if ($payment) {

	      if ($errorCode == '0') {
	        $payment->Status = 'Success';
	        $payment->Message = "PayStation responded with: $errorMessage"; //Error message can be positive e.g: Transaction successful
	      }
	      else {
	        $payment->Status = 'Success';
	        $payment->Message = "PayStation responded with the error: $errorMessage"; 
	      }
	      
	      $payment->write();
				$payment->redirectToOrder();
	    }
	    else {
	      //TODO return meaningful error to user
	      SS_Log::log(new Exception(print_r('cannot find the payment', true)), SS_Log::NOTICE);
	    }
	  }
	  else {
	    //TODO return meaningful error to user
	    SS_Log::log(new Exception(print_r('the transaction ID does not exist', true)), SS_Log::NOTICE);
	  }
	}
	
}