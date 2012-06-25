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
  /**
   * The PayPal method to be passed from PayPal_Controller
   * 
   * @var String
   */
  protected $paypalMethod;
  
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
    return Config::inst()->get('PayPal_Gateway', self::get_type());
  }
  
  /**
   * Get the PayPal URL (Live or Sandbox)
   */
  public static function get_url() {
    return $config['url'];
  }
  
  /**
   * Get the authentication information (username, password, api signature)
   * 
   * @return array 
   */
  public static function get_authentication() {
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
  
  public function setPayPalMethod($method) {
    $this->paypalMethod = $method;
  }
  
  public function process($data) {
    $authentication = self::get_authentication();
    
    // Prepare the payment data in PayPal's format
    // TODO: Check if credit card data and return url is set
    $this->postData['PAYMENTACTION'] = self::$action;
    $this->postData['AMT'] = $data['Amount'];
    $this->postData['CURRENCY'] = $data['Currency'];
    $this->postData['IP'] = $_SERVER['REMOTE_ADDR'];
    $this->postData['USER'] = $authentication['user'];
    $this->postData['PWD'] = $authentication['password'];
    $this->postData['SIGNATURE'] = $authentication['signature'];
  }
  
  public function getResponse($response) { 
    // To be overriden by subclasses
  }
}

class PayPalDirect_Gateway extends PayPal_Gateway {
  protected $paypalMethod = 'DoDirectPayment';
  
  public function process($data) {
    parent::process($data);
    
    // Add credit card data. May have to parse the data to fit PayPal's format
    $this->postData['ACCT'] = $data['CardNumber'];
    $this->postData['EXPDATE'] = $data['DateExpiry'];
    $this->postData['CVV2'] = $data['Cvc2'];
    // And billing information as well
    
    // Post the data to PayPal server
    return $this->postPaymentData($this->postData);
  }
  
  public function getResponse($response) {
    
  }
}

class PayPalDirect_Production_Gateway extends PayPalDirect_Gateway { }

class PayPalDirect_Dev_Gateway extends PayPalDirect_Gateway { }

class PayPalExpress_Gateway extends PayPal_Gateway {
  protected $paypalMethod = 'SetExpressCheckout';
  
  public function process($data) {
    parent::process($data);
  
    // Add return url
    $this->postData['RETURNURL'] = $this->returnURL;
    
    // Post the data to PayPal server
    return $this->postPaymentData($this->postData);
  }
  
  public function getResponse($response) {
    
  }
}

class PayPalExpress_Production_Gateway extends PayPalExpress_Gateway { }

class PayPalExpress_Dev_Gateway extends PayPalExpress_Gateway { }
