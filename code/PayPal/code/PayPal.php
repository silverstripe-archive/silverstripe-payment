<?php
/**
 * Configuration guide for PayPal payment:
 * In Payment settings:
 *   supported_methods:
 *     PayPalDirect:
 *       PayPal_Controller
 * or
 *     PayPalExpress:
 *       PayPal_Controller
 *     
 */

class PayPal extends Payment { }

class PayPalDirect extends PayPal { }

class PayPalExpress extends PayPal { }

class PayPal_Gateway extends Payment_Gateway {
  
  const SUCCESS_CODE = 'Success';
  const SUCCESS_WARNING = 'SuccessWithWarning';
  const FAILURE_CODE = 'Failure';
  
  /**
   * The data to be posted to PayPal server
   * 
   * @var array
   */
  protected $postData;
  
  /**
   * Get the PayPal configuration 
   */
  public static function get_config() {
    return Config::inst()->get('PayPal_Gateway', self::get_environment());
  }
  
  /**
   * Get the PayPal URL (Live or Sandbox)
   */
  public static function get_url() {
    $config = self::get_config();
    return $config['url'];
  }
  
  /**
   * Get the authentication information (username, password, api signature)
   * 
   * @return array 
   */
  public static function get_authentication() {
    $config = self::get_config();
    return $config['authentication'];
  }
  
  /**
   * Get the payment action: 'Sale', 'Authorization', etc from yaml config
   */
  public static function get_action() {
    return Config::inst()->get('PayPal_Gateway', 'action');
  }
  
  public function __construct() {
    $this->gatewayURL = self::get_url();
  }
  
  /**
   * Clear the data array and prepare for a new post to PayPal.
   * Add the basic information to the data array
   */
  public function preparePayPalPost() {
    $authentication = self::get_authentication();
    
    $this->postData = array();
    $this->postData['USER'] = urlencode($authentication['username']);
    $this->postData['PWD'] = urlencode($authentication['password']);
    $this->postData['SIGNATURE'] = urlencode($authentication['signature']);
    $this->postData['VERSION'] = urlencode('51.0');
  }
  
  public function process($data) {
    $this->preparePayPalPost();
    $this->postData['PAYMENTACTION'] = urlencode(self::get_action());
    $this->postData['AMT'] = urlencode($data['Amount']);
    $this->postData['CURRENCY'] = urlencode($data['Currency']);
  }
  
  /**
   * Parse the raw data and response from gateway
   *
   * @param unknown_type $response
   * @return the parsed array
   */
  public function parseResponse($response) {
    parse_str($response->getBody(), $responseArr);
    return $responseArr;
  }
  
  public function getResponse($response) { 
    // To be overriden by subclasses
  }
}

class PayPalDirect_Gateway extends PayPal_Gateway {

  public function process($data) {
    parent::process($data);
    
    $this->postData['METHOD'] = 'DoDirectPayment';
    // Add credit card data. May have to parse the data to fit PayPal's format
    $this->postData['ACCT'] = $data['CardNumber'];
    $this->postData['EXPDATE'] = $data['DateExpiry'];
    $this->postData['CVV2'] = $data['Cvc2'];
    
    // And billing information as well
    
    // Post the data to PayPal server
    return $this->postPaymentData($this->postData);
  }
  
  public function getResponse($response) {
    $responseArr = $this->parseResponse($response);
    
    switch ($responseArr['ACK']) {
      case self::SUCCESS_CODE:
      case self::SUCCESS_WARNING:
        return new Payment_Gateway_Result(Payment_Gateway_Result::SUCCESS);
        break;
      case self::FAILURE_CODE:
        return new Payment_Gateway_Result(Payment_Gateway_Result::FAILURE);
        break;
      default:
        return new Payment_Gateway_Result();
        break;
    }
  }
}

class PayPalDirect_Gateway_Production extends PayPalDirect_Gateway { }

class PayPalDirect_Gateway_Dev extends PayPalDirect_Gateway { }

class PayPalExpress_Gateway extends PayPal_Gateway {
  /**
   * The PayPal token for this transaction
   * 
   * @var String
   */
  private $token;
  
  public function process($data) {
    parent::process($data);
    
    $this->postData['METHOD'] = 'SetExpressCheckout';
    // Add return and cancel urls
    $this->postData['RETURNURL'] = urlencode($this->returnURL);
    $this->postData['CANCELURL'] = urlencode(Director::absoluteBaseURL());
    
    // Post the data to PayPal server to get the token
    $response = $this->parseResponse($this->postPaymentData($this->postData));
    
    if ($token = $response['TOKEN']) {    
      $this->token = $token;
      // If Authorization successful, redirect to PayPal to complete the payment
      Controller::curr()->redirect(self::get_url() . "?cmd=_express-checkout&token=$token");
    } else {
      // Otherwise, do something...
    }
  }
  
  public function getResponse($response) {
    // Get the payer information
    $this->preparePayPalPost();
    $this->postData['METHOD'] = 'GetExpressCheckoutDetails';
    $this->postData['TOKEN'] = urlencode($this->token);
    $response = $this->parseResponse($this->postPaymentData($this->data));
    
    // If successful, complete the payment
    if ($response['ACK'] == self::SUCCESS_CODE || $response['ACK'] == self::SUCCESS_WARNING) {
      $payerID = $response['PAYERID'];
      
      $this->preparePayPalPost();
      $this->postData['METHOD'] = 'DoExpressCheckoutPayment';
      $this->postData['PAYERID'] = urlencode($payerID);
      $this->postData['PAYMENTREQUEST_0_PAYMENTACTION'] = urlencode(self::get_action());
      $this->postData['TOKEN'] = urlencode($this->token);
      $response = $this->parseResponse($this->postPaymentData($this->data));
      
      switch ($responseArr['ACK']) {
        case self::SUCCESS_CODE:
        case self::SUCCESS_WARNING:
          return new Payment_Gateway_Result(Payment_Gateway_Result::SUCCESS);
          break;
        case self::FAILURE_CODE:
          return new Payment_Gateway_Result(Payment_Gateway_Result::FAILURE);
          break;
        default:
          return new Payment_Gateway_Result();
          break;
      }
    } 
  }
}

class PayPalExpress_Gateway_Production extends PayPalExpress_Gateway { }

class PayPalExpress_Gateway_Dev extends PayPalExpress_Gateway { }
