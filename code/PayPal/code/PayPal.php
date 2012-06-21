<?php

class PayPal_Payment extends Payment {
  
}

class PayPal_Gateway extends Payment_Gateway {
  /**
   * PayPal authentication info
   */
  protected static $user;
  protected static $password;
  protected static $signature;
  
  /**
   * The payment action: 'Sale', 'Authorization', etc
   * TODO: Replace static with yaml config
   * 
   * @var String
   */
  protected static $action = 'Sale';
  
  /**
   * The PayPal method to be passed from PayPal_Controller
   * 
   * @var String
   */
  protected $method;
  
  public function setMethod($method) {
    $this->method = $method;
  }
  
  public static function set_authentication($user, $password, $signature) {
    self::$user = $user;
    self::$password = $password;
    self::$signature = $signature;
  }
  
  public static function set_action($action) {
    self::$action = $action;
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
  
}

class PayPalDirect_Gateway_Production extends PayPalDirect_Gateway {
  
  protected $gatewayURL = 'https://api-3t.paypal.com/nvp';
}

class PayPalDirect_Gateway_Dev extends PayPalDirect_Gateway {
  
  protected $gatewayURL = 'https://api-3t.sandbox.paypal.com/nvp';
}

class PayPalExpress_Gateway extends PayPal_Gateway {

}

class PayPalExpress_Gateway_Production extends PayPalExpress_Gateway {

  protected $gatewayURL = 'https://api-3t.paypal.com/nvp';
}

class PayPalExpress_Gateway_Dev extends PayPalExpress_Gateway {

  protected $gatewayURL = 'https://api-3t.sandbox.paypal.com/nvp';
}

class PayPal_Controller extends Payment_Controlelr {
  /**
   * The PayPal method in use: 'DoDirectPayment', 'SetExpressCheckout', etc
   *
   * @var String
   */
  protected $method;
  
  protected function getGateway() {
    $gateway = parent::getGateway();
    $gateway->setMethod($this->method);
  
    return $gateway;
  }
}

class PayPal_DirectPayment_Controller extends Payment_Controller_MerchantHosted {
  
  protected $method = 'DoDirectPayment'; 
}

class PayPal_ExpressPayment_Controller extends Payment_Controller_GatewayHosted {

  protected $method = 'SetExpressCheckout';
}

