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

class PayPal extends Payment {
  
}

class PayPal_Gateway extends Payment_Gateway {
  /**
   * The PayPal method to be passed from PayPal_Controller
   * 
   * @var String
   */
  protected $paypalMethod;
  
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
    parent::__construct();
  
    $this->gatewayURL = self::get_url();
  }
  
  public function setPayPalMethod($method) {
    $this->paypalMethod = $method;
  }
  
  public function process($data) {
    // Prepare the payment data in PayPal's format
    // TODO: Check if credit card data and return url is set
    $postData = array(
      'PAYMENTACTION' => self::$action,
      'AMT' => $data['Amount'],
      'CURRENCY' => $data['Currency'],
      'IP' => $_SERVER['REMOTE_ADDR'],
      'USER' => self::$user,
      'PWD' => self::$password,
      'SIGNATURE' => self::$signature
    ); 
    
    // Post the data to PayPal server
    $response = $this->postPaymentData($postData);
    
    // TODO: if return url is set for ExpressCheckout, no need to do manual redirection
    Session::set('PayPalResponse', $response);
    Director::redirect($this->returnURL);
  }
}

class PayPalDirect_Gateway extends PayPal_Gateway {
  protected $paypalMethod = 'DoDirectPayment';
}

class PayPalDirect_Gateway_Production extends PayPalDirect_Gateway { }

class PayPalDirect_Gateway_Dev extends PayPalDirect_Gateway { }

class PayPalExpress_Gateway extends PayPal_Gateway {
  protected $paypalMethod = 'SetExpressCheckout';
}

class PayPalExpress_Gateway_Production extends PayPalExpress_Gateway { }

class PayPalExpress_Gateway_Dev extends PayPalExpress_Gateway { }
