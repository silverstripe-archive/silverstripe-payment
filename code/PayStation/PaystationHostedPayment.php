<?php
/**
 * PayStation hosted payment, sends user to a PayStation hosted page in order for them
 * to enter credit card details (@see PaystationHostedPayment::$url).
 * 
 * After payment user is redirected by PayStation to a URL that is set for their PayStation account,
 * recommend setting this URL to http://yoursite.com/paystation/complete so that the 
 * {@link PaystationHostedPayment_Handler} can update the {@link Payment} status and redirect
 * user to the {@link Order} details page after payment is processed.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @package payment
 */
class PaystationHostedPayment extends Payment {
  
  /**
   * Database fields for {@link PaystationHostedPayment}
   * Includes TransactionID from PayStation so that {@link Payment} can be updated
   * after payment processed (@see PaystationHostedPayment_Handler::complete()}.
   * 
   * @var Array
   */
  static $db = array(
		'TransactionID' => 'Varchar' //TransactionID fr
	);
	
	/**
	 * URL for transaction initiation
	 * 
	 * @see PaystationHostedPayment::processPayment()
	 * @var unknown_type
	 */
	protected static $url = 'https://www.paystation.co.nz/direct/paystation.dll';
	
	/**
	 * PayStation ID for a PayStation account.
	 * 
	 * @var String
	 */
	protected static $paystation_id;
	
	/**
	 * Gateway ID for a PayStation account.
	 * 
	 * @var String
	 */
	protected static $gateway_id;
	
	/**
	 * Flag for test mode, when payment gateway is used on developer account rather 
	 * than being live.
	 * 
	 * @var Boolean
	 */
	protected static $test_mode = false;

	/**
	 * Link to privacy policy for displaying on a checkout form or similar.
	 * 
	 * @var String
	 */
	protected static $privacy_link = 'http://paystation.co.nz/privacy-policy';
	
	/**
	 * Logo image for displaying on a checkout form or similar.
	 * @var unknown_type
	 */
	protected static $logo = 'payment/images/paystation.jpg';
	
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
	
	/**
	 * Set PayStation ID
	 * 
	 * @param String $id
	 */
  static function set_paystation_id($id) {
		self::$paystation_id = $id;
	}
	
	/**
	 * Set gateway ID
	 * 
	 * @param String $id
	 */
  static function set_gateway_id($id) {
		self::$gateway_id = $id;
	}
	
	/** 
	 * Turn on test mode by setting to 'true'
	 * 
	 * @see PaystationHostedPayment::$test_mode
	 */
	static function set_test_mode() {
		self::$test_mode = true;
	}

	/**
	 * Set a site description
	 * 
	 * @param String $description
	 */
  static function set_site_description($description) {
		self::$site_description = $description;
	}
	
	/**
	 * Set a merchant reference
	 * 
	 * @param String $ref
	 */
  static function set_merchant_reference($ref) {
		self::$merchant_ref = urlencode($ref);
	}
	
	/**
	 * Create payment form fields for display on a checkout form or similar, in this 
	 * case credit card details etc. are entered on PayStation website so fields are mostly
	 * for PayStation and Credit Card logos.
	 * 
	 * @see Payment::getPaymentFormFields()
	 * @return FieldSet
	 */
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
	
	/**
	 * Get payment form requirements, PayStation hosts the payment form so no requirements.
	 * 
	 * @see Payment::getPaymentFormRequirements()
	 * @return Null
	 */
	function getPaymentFormRequirements() {
		return null;
	}
	
	/**
	 * Process the payment by initiating a transaction with PayStation, if initiation returns
	 * correctly redirect user to PayStation website to complete payment with credit card details etc.
	 * 
	 * @see Payment::processPayment()
	 * @return Payment_Result A result which may be Processing or Failure
	 */
	function processPayment($data, $form) {

	  $paystationURL	= self::$url;
    $amount		= $this->Amount->Amount * 100;
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
	
	/**
	 * Initiate transation with PayStation, this function mostly taken from PayStation directly for 
	 * 3-party PHP demo.
	 * 
	 * If receiving a cURL error like: 'Curl error: SSL certificate problem, verify that the CA cert is OK.'
	 * Possible that OpenSSL is not installed correctly. Can bypass certificate checks by setting:
	 * curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
	 * curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
	 * 
	 * @param String $url PayStation URL to POST to
	 * @param String $params Data to POST
	 * @return Mixed String|False On success returns string of XML respone from PayStation, failure returns false
	 */
	private function directTransaction ($url, $params){

  	$userAgent = null;
  	
  	if (isset($_SERVER['HTTP_USER_AGENT'])) {
  	  $userAgent = $_SERVER['HTTP_USER_AGENT'];
  	}
  	
  	$defined_vars = get_defined_vars();
  	if (isset($defined_vars['HTTP_USER_AGENT'])) {
  	  $userAgent = $defined_vars['HTTP_USER_AGENT'];
  	}

  	//POST data using cURL
  	$ch = curl_init();
  	curl_setopt($ch, CURLOPT_POST,1);
  	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
  	curl_setopt($ch, CURLOPT_URL, $url);
  	curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 2);
  	curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
  	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  	$result = curl_exec($ch);
  	
  	//Logging if curl errors
  	if (curl_errno($ch)) {
  	  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  	  SS_Log::log(new Exception(print_r("HTTP Code: $httpcode", true)), SS_Log::NOTICE);
  	  SS_Log::log(new Exception(print_r("Curl Error: ". curl_error($ch), true)), SS_Log::NOTICE);
    }

    curl_close ($ch);
  	return $result;	
  }
	
  /**
   * Create a random string payment session ID, copied from PayStation 3-party PHP demo code.
   * 
   * @param Int $min
   * @param Int $max
   * @return String A new payment session ID
   */
	private function makePaystationSessionID ($min = 8, $max = 8) {

    $seed = (double)microtime()*getrandmax();
    srand($seed);
  
    //Create string of $max characters with ASCII values of 40-122
    $pass = '';
    $p=0; while ($p < $max):
      $r=123-(rand()%75);
      $pass.=chr($r);
    $p++; endwhile;
  
    $pass=preg_replace("/[^a-zA-NP-Z1-9]+/","",$pass);
  
    //If string is too short, remake it
    if (strlen($pass)<$min):
      $pass=$this->makePaystationSessionID($min,$max);
    endif;
  
    return $pass;
  }
}

/**
 * Handler for responsed from the PayStation site. 
 * 
 * With PayStation you need to set a return URL for your PayStation account which PayStation
 * will use to redirect your customers after payment has processed. Good idea to use:
 * http://yoursite.com/paystation/complete
 * as the URL, then it can be handled in PaystationHostedPayment_Handler::complete().
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @package payment
 */
class PaystationHostedPayment_Handler extends Controller {

  /**
   * URL segment, see payment _config.php directives.
   * 
   * @var String
   */
	static $URLSegment = 'paystation';
	
	/**
	 * Convenience method for returning the complete payment link, not really used in this class.
	 * 
	 * @return String URL for complete payment link
	 */
	static function complete_link() {
		return self::$URLSegment . '/complete';
	}
	
	/**
	 * After payment is completed on PayStation customer can be reirected here, {@link Payment} will
	 * be updated and customer will be redirected to their Order if Payment::redirectToOrder() is available.
	 */
	function complete() {

	  $request = $this->getRequest();
	  $transactionID = $request->getVar('ti');
	  $errorCode = $request->getVar('ec');
	  $errorMessage = $request->getVar('em');
	  
	  if ($transactionID) {
	    
	    $payment = DataObject::get_one('PaystationHostedPayment', 'TransactionID = \'' . Convert::raw2sql($transactionID) . '\'');
	    
	    if ($payment) {

	      if ($errorCode == '0') {
	        $payment->Status = 'Success';
	        $payment->Message = "PayStation responded with: $errorMessage"; //Error message can be positive e.g: Transaction successful
	      }
	      else {
	        $payment->Status = 'Failure';
	        $payment->Message = "PayStation responded with the error: $errorMessage"; 
	      }
	      
	      $payment->write();
				$payment->redirectToOrder();
	    }
	    else {
	      SS_Log::log(new Exception(print_r("Cannot find Payment for TransactionID: $transactionID", true)), SS_Log::NOTICE);
	      Debug::friendlyError();
	    }
	  }
	  else {
	    SS_Log::log(new Exception(print_r('Transaction ID was not passed from PayStation, here is the request:', true)), SS_Log::NOTICE);
	    SS_Log::log(new Exception(print_r($request, true)), SS_Log::NOTICE);
	    Debug::friendlyError();
	  }
	}
}