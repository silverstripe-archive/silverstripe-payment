<?php

class PaystationHostedPayment extends Payment {

	protected static $privacy_link = 'http://paystation.co.nz/privacy-policy';
	protected static $logo = 'payment/images/paystation.jpg';
	protected static $url = 'https://www.paystation.co.nz/dart/darthttp.dll?paystation&pi=%s&ms=%s&am=%s';
	protected static $test_mode = false;
	protected static $merchant_id;
	protected static $merchant_ref;
	
	protected static $paystation_id;
	protected static $gateway_id;
	
	static function set_test_mode() {
		self::$test_mode = true;
	}

	static function set_merchant_id($merchant_id) {
		self::$merchant_id = $merchant_id;
	}

	static function set_merchant_ref($merchant_ref) {
		self::$merchant_ref = $merchant_ref;
	}
	
  static function set_paystation_id($id) {
		self::$paystation_id = $id;
	}
	
  static function set_gateway_id($id) {
		self::$gateway_id = $id;
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

	  $amount = $data['Amount'];
	  
	  $paystationURL	= "https://www.paystation.co.nz/direct/paystation.dll";
    $amount		= $data['Amount'] * 100;
    $pstn_pi	= self::$paystation_id; //Paystation ID
    $pstn_gi	= self::$gateway_id; //Gateway ID
    $site = 'testing'; //site can be used to differentiate transactions from different websites in admin.
    $pstn_mr = urlencode('from sample code'); //merchant reference is optional, but is a great way to tie a transaction in with a customer (this is displayed in Paystation Administration when looking at transaction details). Max length is 64 char. Make sure you use it!
    
    $testMode = true;
    
  	$merchantSession	= urlencode($site.'-'.time().'-'.$this->makePaystationSessionID(8,8)); //max length of ms is 64 char 
  	
    //$url = $paystation."&am=".$amount."&pi=".$paystationid."&ms=".$merchantsession.'&merchant_ref='.$merchant_ref;
    $paystationParams = "paystation&pstn_pi=".$pstn_pi."&pstn_gi=".$pstn_gi."&pstn_ms=".$merchantSession."&pstn_am=".$amount."&pstn_mr=".$pstn_mr."&pstn_nr=t";
    
    if 	($testMode == true){
    	$paystationParams = $paystationParams."&pstn_tm=t";
    }
    
    //Do Transaction Initiation POST
    $initiationResult=$this->directTransaction($paystationURL, $paystationParams);
    $p = xml_parser_create();
    xml_parse_into_struct($p, $initiationResult, $vals, $tags);
    xml_parser_free($p);
    

    //Does this ever get called? long payment process
  	for ($j=0; $j < count($vals); $j++) {
    	if (!strcmp($vals[$j]["tag"],"DIGITALORDER") && isset($vals[$j]["value"])){
    		//get digital order URL
    		$digitalOrder=$vals[$j]["value"];
    		
    		SS_Log::log(new Exception(print_r($digitalOrder, true)), SS_Log::NOTICE);
    		
    	}
    	if (!strcmp($vals[$j]["tag"],"PAYSTATIONTRANSACTIONID") && isset($vals[$j]["value"])){
    		//get Paystation Transaction ID for reference
    		$paystationTransactionID=$vals[$j]["value"];
    	}
    }
    
	  if (isset($digitalOrder) && $digitalOrder) {
	    
	    
  	  $this->Status = "Pending";
  		$this->write();
  		
  		if ($digitalOrder){
  			Director::redirect($digitalOrder); //redirect to payment gateway
  			return new Payment_Processing();
  		}

  		
    	//header("Location:".$digitalOrder);
    	//exit();
    } 
    else {
      
      $this->Message = "PayPal could not be contacted";
  		$this->Status = 'Failure';
  		$this->write();
  		
  		return new Payment_Failure($this->Message);
      
      
      //header ("Content-Type:text/xml"); 
      //echo $initiationResult;
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