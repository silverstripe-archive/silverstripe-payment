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

class PayPalGateway extends PaymentGateway {
  
  const SUCCESS_CODE = 'Success';
  const SUCCESS_WARNING = 'SuccessWithWarning';
  const FAILURE_CODE = 'Failure';
  const PAYPAL_VERSION = '51.0'; 
  
  /**
   * The data to be posted to PayPal server
   * 
   * @var array
   */
  protected $postData;
  
  /**
   * The PayPal base redirection URL to process payment 
   */
  static $payPalRedirectURL = 'https://www.paypal.com/webscr';
  static $payPalSandboxRedirectURL = 'https://www.sandbox.paypal.com/webscr';
  
  /**
   * Get the PayPal configuration 
   */
  public static function get_config() {
    return Config::inst()->get('PayPalGateway', self::get_environment());
  }
  
  /**
   * Get the PayPal URL (Live or Sandbox)
   */
  public static function get_url() {
    $config = self::get_config();
    return $config['url'];
  }
  
  /**
   * Get the PayPal redirect URL (Live or Sandbox)
   */
  public static function get_paypal_redirect_url() {
    switch (self::get_environment()) {
      case 'live':
        return self::$payPalRedirectURL;
        break;
      case 'dev':
        return self::$payPalSandboxRedirectURL;
        break;
      default:
        return null;
    }
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
    return Config::inst()->get('PayPalGateway', 'action');
  }
  
  public function __construct() {
    $this->gatewayURL = self::get_url();
  }
  
  public function getSupportedCurrencies() {
    return array('AUD', 'CAD', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'JPY',
                 'NOK', 'NZD', 'PLN', 'GBP', 'SGD', 'SEK', 'CHF', 'USD'); 
  }
  
  public function getSupportedCreditCardType() {
    return array(
      'Visa' => 'Visa',
      'MasterCard' => 'Master',
      'Amex' => 'American Express',
      'Maestro' => 'Maestro'
    );
  }
  
  /**
   * Clear the data array and prepare for a new post to PayPal.
   * Add the basic information to the data array
   */
  public function preparePayPalPost() {
    $authentication = self::get_authentication();
    
    $this->postData = array();
    $this->postData['USER'] = $authentication['username'];
    $this->postData['PWD'] = $authentication['password'];
    $this->postData['SIGNATURE'] = $authentication['signature'];
    $this->postData['VERSION'] = self::PAYPAL_VERSION;
  }
  
  public function process($data) {
    $this->preparePayPalPost();
    $this->postData['PAYMENTACTION'] = self::get_action();
    $this->postData['AMT'] = $data['Amount'];
    $this->postData['CURRENCY'] = $data['Currency'];
  }
  
  /**
   * Parse the raw data and response from gateway
   *
   * @param $response - This can be the response string itself or the 
   *                    string encapsulated in a HTTPResponse object
   * @return the parsed array
   */
  public function parseResponse($response) {
    if ($response instanceof RestfulService_Response) {
      parse_str($response->getBody(), $responseArr);
    } else {
      parse_str($response, $responseArr);
    }
    
    return $responseArr;
  }
  
  public function getResponse($response) { 
    // To be overriden by subclasses
  }
  
  /**
   * Override to add buiding query string manually. 
   * TODO: May consider doing this by default if other gateways work similarly
   * 
   * @see PaymentGateway::postPaymentData()
   */
  public function postPaymentData($data, $endpoint = null) {
    $httpQuery = http_build_query($data);
    return parent::postPaymentData($httpQuery);
  }
}