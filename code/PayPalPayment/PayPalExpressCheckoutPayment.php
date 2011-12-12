<?php
/**
 * PayPal Express Checkout Payment
 * @author Jeremy Shipman jeremy [at] burnbright.co.nz
 * 
 * Testing setup:
 * You will need a PayPal sandbox account, along with merchant and customer test accounts,
 * which can be set up by following this guide:
 * https://developer.paypal.com/en_US/pdf/PP_Sandbox_UserGuide.pdf
 * 
 * How to set up:
 * -Set up a paypal merchant account
 * -Log in
 * -Visit 'My Account' > 'Profile'
 * -Click 'API Access' link (listed under Account Information)
 * -Click option 2 : 'Request API credentials'
 * -Choose 'Request API signature', and click 'Agree and Submit'
 * -Enter these details into your mysite/_config.php file with either the set_config_details or set_test_config_details functions
 * 
 * Notes / Troubleshooting:
 * -you must be logged into sandbox to process a test payment.
 * 
 * Developer documentation:
 * Integration guide: https://cms.paypal.com/cms_content/US/en_US/files/developer/PP_ExpressCheckout_IntegrationGuide.pdf
 * API reference: 	  https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/howto_api_reference
 * 
 */

class PayPalExpressCheckoutPayment extends Payment{
	
	static $db = array(
		'Token' => 'Varchar(30)',
		'PayerID' => 'Varchar(30)',
		'TransactionID' => 'Varchar(30)'
	);
	
	protected static $logo = "payment/images/payments/paypal.jpg";
	
	//PayPal URLs
	protected static $test_API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
	protected static $test_PAYPAL_URL = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=";

	protected static $API_Endpoint = "https://api-3t.paypal.com/nvp";
	protected static $PAYPAL_URL = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";
	
	protected static $privacy_link = "https://www.paypal.com/us/cgi-bin/webscr?cmd=p/gen/ua/policy_privacy-outside";
	
	//redirect URLs
	protected static $returnURL = "PaypalExpressCheckoutaPayment_Handler/confirm";
	protected static $cancelURL = "PaypalExpressCheckoutaPayment_Handler/cancel";
	
	//config
	protected static $test_mode = true; //on by default

	protected static $API_UserName;
	protected static $API_Password;
	protected static $API_Signature;
	protected static $sBNCode = null; // BN Code 	is only applicable for partners
	
	protected static $version = '64';
	
	//set custom settings
	protected static $customsettings = array(
		//design
		//'HDRIMG' => "http://www.mysite.com/images/logo.jpg", //max size = 750px wide by 90px high, and good to be on secure server
		//'HDRBORDERCOLOR' => 'CCCCCC', //header border
		//'HDRBACKCOLOR' => '00FFFF', //header background
		//'PAYFLOWCOLOR'=> 'AAAAAA' //payflow colour
		//'PAGESTYLE' => //page style set in merchant account settings
		
		'SOLUTIONTYPE' => 'Sole'//require paypal account, or not. Can be or 'Mark' (required) or 'Sole' (not required)
		//'BRANDNAME'  => 'my site name'//override business name in checkout
		//'CUSTOMERSERVICENUMBER' => '0800 1234 5689'//number to call to resolve payment issues
		//'NOSHIPPING' => 1 //disable showing shipping details
	);
	
	static function set_test_config_details($username,$password,$signature,$sbncode = null){
		self::$API_UserName = $username;
		self::$API_Password = $password;
		self::$API_Signature = $signature;
		self::$sBNCode = $sbncode;
		self::$test_mode = true;
	}
	
	static function set_config_details($username,$password,$signature,$sbncode = null){
		self::$API_UserName = $username;
		self::$API_Password = $password;
		self::$API_Signature = $signature;
		self::$sBNCode = $sbncode;
		self::$test_mode = false;
	}
	
	static function set_custom_settings(array $design){
		self::$customsettings = array_merge(self::$customsettings,$design);
	}
	
	//main processing function
	function processPayment($data, $form) {
		
		//sanity checks for credentials
		if(!self::$API_UserName || !self::$API_Password || !self::$API_Signature){
			user_error('You are attempting to make a payment without the necessary credentials set', E_USER_ERROR);
		}
		
		$paymenturl = $this->getTokenURL($this->Amount->Amount, $this->Amount->Currency, $data);
		
		//SS_Log::log(new Exception(print_r(Director::absoluteURL(self::$returnURL,true), true)), SS_Log::NOTICE);
		
		$this->Status = "Pending";
		$this->write();
		
		if($paymenturl){
			Director::redirect($paymenturl); //redirect to payment gateway
			return new Payment_Processing();
		}
		
		$this->Message = "PayPal could not be contacted";
		$this->Status = 'Failure';
		$this->write();
		
		return new Payment_Failure($this->Message);
	}
	

	/**
	 * Requests a Token url, based on the provided Name-Value-Pair fields
	 * See docs for more detail on these fields:
	 * https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_nvp_r_SetExpressCheckout
	 * 
	 * Note: some of these values will override the paypal merchant account settings.
	 * Note: not all fields are listed here.
	 */	
	protected function getTokenURL($paymentAmount, $currencyCodeType, $extradata = array()){
		
		$data = array(
			//payment info
			'PAYMENTREQUEST_0_AMT' => $paymentAmount,
			'PAYMENTREQUEST_0_CURRENCYCODE' => $currencyCodeType, //TODO: check to be sure all currency codes match the SS ones

			//TODO: include individual costs: shipping, shipping discount, insurance, handling, tax??
			//'PAYMENTREQUEST_0_ITEMAMT' => //item(s)
			//'PAYMENTREQUEST_0_SHIPPINGAMT' //shipping
			//'PAYMENTREQUEST_0_SHIPDISCAMT' //shipping discount
			//'PAYMENTREQUEST_0_HANDLINGAMT' //handling
			//'PAYMENTREQUEST_0_TAXAMT' //tax
			
			//'PAYMENTREQUEST_0_INVNUM' => $this->PaidObjectID //invoice number
			//'PAYMENTREQUEST_0_TRANSACTIONID' => $this->ID //Transactino id
			//'PAYMENTREQUEST_0_DESC' => //description
			//'PAYMENTREQUEST_0_NOTETEXT' => //note to merchant
			
			//'PAYMENTREQUEST_0_PAYMENTACTION' => , //Sale, Order, or Authorization
			//'PAYMENTREQUEST_0_PAYMENTREQUESTID'
			
			//return urls
			'RETURNURL' => Director::absoluteURL(self::$returnURL,true),
			'CANCELURL' => Director::absoluteURL(self::$cancelURL,true),
			//'PAYMENTREQUEST_0_NOTIFYURL' => //Instant payment notification
			 
			//'CALLBACK'
			//'CALLBACKTIMEOUT'
						
			//shipping display
			//'REQCONFIRMSHIPPING' //require that paypal account address be confirmed
			'NOSHIPPING' => 1, //show shipping fields, or not 0 = show shipping, 1 = don't show shipping, 2 = use account address, if none passed
			//'ALLOWOVERRIDE' //display only the provided address, not the one stored in paypal
			
			//TODO: Probably overkill, but you can even include the prices,qty,weight,tax etc for individual sale items
					
			//other settings
			//'LOCALECODE' => //locale, or default to US
			'LANDINGPAGE' => 'Billing' //can be 'Billing' or 'Login'

		);
		
		if(!isset($extradata['Name'])){
			$arr =  array();
			if(isset($extradata['FirstName'])) $arr[] = $extradata['FirstName'];
			if(isset($extradata['MiddleName'])) $arr[] = $extradata['MiddleName'];
			if(isset($extradata['Surname'])) $arr[] = $extradata['Surname'];
			
			$extradata['Name'] = implode(' ',$arr);
		}
			
		
		//add member & shipping fields ...this will pre-populate the paypal login / create account form
		foreach(array(
			'Email' => 'EMAIL',
			'Name' => 'PAYMENTREQUEST_0_SHIPTONAME',
			'Address' => 'PAYMENTREQUEST_0_SHIPTOSTREET',
			'AddressLine2' => 'PAYMENTREQUEST_0_SHIPTOSTREET2',
			'City' => 'PAYMENTREQUEST_0_SHIPTOCITY',
			'State' => 'PAYMENTREQUEST_0_SHIPTOSTATE',
			'PostalCode' => 'PAYMENTREQUEST_0_SHIPTOZIP',
			'HomePhone' => 'PAYMENTREQUEST_0_SHIPTOPHONENUM',
			'Country' => 'PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE'
		) as $field => $val){
			if(isset($extradata[$field])){
				$data[$val] = $extradata[$field];
			}			
		}
		
		//set design settings
		$data = array_merge(self::$customsettings,$data);

		$response = $this->apiCall('SetExpressCheckout',$data);
		
		if(!isset($response['ACK']) ||  !(strtoupper($response['ACK']) == "SUCCESS" || strtoupper($response['ACK']) == "SUCCESSWITHWARNING")){
			return null;
		}
		
		//get and save token for later
		$token = $response['TOKEN'];
		$this->Token = $token;
		$this->write();

		return $this->getPayPalURL($token);
	}
	
	/**
	 * see https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_nvp_r_DoExpressCheckoutPayment
	 */
	function confirmPayment(){
		
				
		$data = array(
			'PAYERID' => $this->PayerID,
			'TOKEN' => $this->Token,
			'PAYMENTREQUEST_0_PAYMENTACTION' => "Sale",
			'PAYMENTREQUEST_0_AMT' => $this->Amount->Amount,
			'PAYMENTREQUEST_0_CURRENCYCODE' => $this->Amount->Currency,
			'IPADDRESS' => urlencode($_SERVER['SERVER_NAME'])
		);
		
		$response = $this->apiCall('DoExpressCheckoutPayment',$data);
		
		if(!isset($response['ACK']) ||  !(strtoupper($response['ACK']) == "SUCCESS" || strtoupper($response['ACK']) == "SUCCESSWITHWARNING")){
			return null;
		}
		
		
		if(isset($response["PAYMENTINFO_0_TRANSACTIONID"])){
			$this->TransactionID	= $response["PAYMENTINFO_0_TRANSACTIONID"]; 	//' Unique transaction ID of the payment. Note:  If the PaymentAction of the request was Authorization or Order, this value is your AuthorizationID for use with the Authorization & Capture APIs.
		} 
		//$transactionType 		= $response["PAYMENTINFO_0_TRANSACTIONTYPE"]; //' The type of transaction Possible values: l  cart l  express-checkout 
		//$paymentType			= $response["PAYMENTTYPE"];  	//' Indicates whether the payment is instant or delayed. Possible values: l  none l  echeck l  instant 
		//$orderTime 				= $response["ORDERTIME"];  		//' Time/date stamp of payment
		
		//TODO: should these be updated like this?
		//$this->Amount->Amount	= $response["AMT"];  			//' The final amount charged, including any shipping and taxes from your Merchant Profile.
		//$this->Amount->Currency= $response["CURRENCYCODE"];  	//' A three-character currency code for one of the currencies listed in PayPay-Supported Transactional Currencies. Default: USD. 
		
		//TODO: store this extra info locally?
		//$feeAmt					= $response["FEEAMT"];  		//' PayPal fee amount charged for the transaction
		//$settleAmt				= $response["SETTLEAMT"];  		//' Amount deposited in your PayPal account after a currency conversion.
		//$taxAmt					= $response["TAXAMT"];  		//' Tax charged on the transaction.
		//$exchangeRate			= $response["EXCHANGERATE"];  	//' Exchange rate if a currency conversion occurred. Relevant only if your are billing in their non-primary currency. If the customer chooses to pay with a currency other than the non-primary currency, the conversion occurs in the customer’s account.

		if(isset($response["PAYMENTINFO_0_PAYMENTSTATUS"])){
			
			switch(strtoupper($response["PAYMENTINFO_0_PAYMENTSTATUS"])){
				case "PROCESSED":
				case "COMPLETED":
					$this->Status = 'Success';
					$this->Message = "The payment has been completed, and the funds have been successfully transferred";
					break;
				case "EXPIRED":
					$this->Message = "The authorization period for this payment has been reached";
					$this->Status = 'Failure';
					break;	
				case "DENIED":
					$this->Message = "Payment was denied";
					$this->Status = 'Failure';
					break;	
				case "REVERSED":
					$this->Status = 'Failure';
					break;	
				case "VOIDED":
					$this->Message = "An authorization for this transaction has been voided.";
					$this->Status = 'Failure';
					break;	
				case "FAILED":
					$this->Status = 'Failure';
					break;					
				case "CANCEL-REVERSAL": // A reversal has been canceled; for example, when you win a dispute and the funds for the reversal have been returned to you.
					break;
				case "IN-PROGRESS":
					$this->Message = "The transaction has not terminated";//, e.g. an authorization may be awaiting completion.";
					break;
				case "PARTIALLY-REFUNDED":
					$this->Message = "The payment has been partially refunded.";
					break;
				case "PENDING":
					$this->Message = "The payment is pending.";
					if(isset($response["PAYMENTINFO_0_PENDINGREASON"])){
						$this->Message .= " ".$this->getPendingReason($response["PAYMENTINFO_0_PENDINGREASON"]);
					}
					break;
				case "REFUNDED":
					$this->Message = "Payment refunded.";
					break;	
				default:
			}	
		}
		//$reasonCode		= $response["REASONCODE"]; 
		
		$this->write();
		
	}
	
	protected function getPendingReason($reason){
		
		switch($reason){
			case "address":
				return "A confirmed shipping address was not provided.";
			case "authorization":
				return "Payment has been authorised, but not settled.";
			case "echeck":
				return "eCheck has not cleared.";
			case "intl":
				return "International: payment must be accepted or denied manually.";
			case "multicurrency":
				return "Multi-currency: payment must be accepted or denied manually.";
			case "order":
			case "paymentreview":
			case "unilateral":
			case "verify":
			case "other":
		}
	}
	
	/**
	 * Handles actual communication with API server.
	 */
	protected function apiCall($method,$data = array()){
		
		$postfields = array(
			'METHOD' => $method,
			'VERSION' => self::$version,
			'USER' => self::$API_UserName,			
			'PWD'=> self::$API_Password,
			'SIGNATURE' => self::$API_Signature,
			'BUTTONSOURCE' => self::$sBNCode
		);
		
		$postfields = array_merge($postfields,$data);
		
		//Make POST request to Paystation via RESTful service
		$paystation = new RestfulService($this->getApiEndpoint(),0); //REST connection that will expire immediately
		$paystation->httpHeader('Accept: application/xml');
		$paystation->httpHeader('Content-Type: application/x-www-form-urlencoded');
		
		$response = $paystation->request('','POST',http_build_query($postfields));	
		
		return $this->deformatNVP($response->getBody());
	}
	
	protected function deformatNVP($nvpstr){
		$intial = 0;
	 	$nvpArray = array();

		while(strlen($nvpstr)){
			//postion of Key
			$keypos= strpos($nvpstr,'=');
			//position of value
			$valuepos = strpos($nvpstr,'&') ? strpos($nvpstr,'&'): strlen($nvpstr);

			/*getting the Key and Value values and storing in a Associative Array*/
			$keyval=substr($nvpstr,$intial,$keypos);
			$valval=substr($nvpstr,$keypos+1,$valuepos-$keypos-1);
			//decoding the respose
			$nvpArray[urldecode($keyval)] =urldecode( $valval);
			$nvpstr=substr($nvpstr,$valuepos+1,strlen($nvpstr));
	     }
		return $nvpArray;
	}
	
	protected function getApiEndpoint(){
		return (self::$test_mode) ? self::$test_API_Endpoint : self::$API_Endpoint;
	}
	
	protected function getPayPalURL($token){
		$url = (self::$test_mode) ? self::$test_PAYPAL_URL : self::$PAYPAL_URL;
		return $url.$token.'&useraction=commit'; //useraction=commit ensures the payment is confirmed on PayPal, and not on a merchant confirm page.
	}
	
	
	function getPaymentFormFields() {
	  
	  Requirements::css('payment/css/PayPalExpressCheckout.css');
	  
		$logo = '<img src="' . self::$logo . '" alt="Credit card payments powered by PayPal"/>';
		$privacyLink = '<a href="' . self::$privacy_link . '" target="_blank" rel="nofollow" title="Read PayPal\'s privacy policy">' . $logo . '</a><br />';
		return new FieldSet(
			//new LiteralField('PayPalInfo', $privacyLink),
			new LiteralField(
				'PayPalPaymentsList',
				
				//TODO: these methods aren't available in all countries
				'<div id="VisaCard" class="PayPalCC"></div>' .
  			'<div id="MasterCard" class="PayPalCC"></div>' .
  			'<div id="AmEx" class="PayPalCC"></div>' .
  			'<div id="Discover" class="PayPalCC"></div>' .
  			'<div id="PayPal" class="PayPalCC"></div>' .
			
//				'<img src="payment/images/payments/methods/visa.jpg" alt="Visa" class="PaypalCC" />' .
//				'<img src="payment/images/payments/methods/mastercard.jpg" alt="MasterCard" class="PaypalCC" />' .
//				'<img src="payment/images/payments/methods/american-express.gif" alt="American Express" class="PaypalCC" />' .
//				'<img src="payment/images/payments/methods/discover.jpg" alt="Discover" class="PaypalCC" />' .
//				'<img src="payment/images/payments/methods/paypal.jpg" alt="PayPal" class="PaypalCC" />'.

			  '<a href="' . self::$privacy_link . '" target="_blank" rel="nofollow" class="PayPalPrivacy" title="Read PayPal\'s privacy policy">PayPal Privacy Policy</a>'
			)
		);
	}

	function getPaymentFormRequirements() {return null;}
	
}

class PaypalExpressCheckoutaPayment_Handler extends Controller{
	
	protected $payment = null; //only need to get this once
	
	static $allowed_actions = array(
		'confirm',
		'cancel'
	);
	
	function payment(){
		if($this->payment){
			return $this->payment;
		}
		
		if($token = Controller::getRequest()->getVar('token')){
			$p =  DataObject::get_one('PayPalExpressCheckoutPayment',"\"Token\" = '$token' AND \"Status\" = 'Pending'");
			$this->payment = $p;
			return $p;
		}
		return null;
	}
	
	function confirm($request){
		
		//TODO: pretend the user confirmed, and skip straight to results. (check that this is allowed)
		//TODO: get updated shipping details from paypal??
		
		if($payment = $this->payment()){
			
			if($pid = Controller::getRequest()->getVar('PayerID')){
				$payment->PayerID = $pid;
				$payment->write();
				
				$payment->confirmPayment();
			}
			
		}else{
			//something went wrong?	..perhaps trying to pay for a payment that has already been processed	
		}
		
		$this->doRedirect();
		return;
	}
	
	function cancel($request){

		if($payment = $this->payment()){
			
			//TODO: do API call to gather further information
			
			$payment->Status = "Failure";
			$payment->Message = "User cancelled";
			$payment->write();
		}
		
		$this->doRedirect();
		return;
	}
	
	function doRedirect(){
		
		$payment = $this->payment();
		if($payment && $obj = $payment->PaidObject()){
			Director::redirect($obj->Link());
			return;
		}
		
		Director::redirect(Director::absoluteURL('home',true)); //TODO: make this customisable in Payment_Controllers
		return;
	}
}