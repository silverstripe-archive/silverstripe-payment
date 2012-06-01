<?php

/**
 * Abstract class for a number of payment controllers. 
 * 
 * @package payment
 */
class Payment_Controller extends Controller {
  
  static $URLSegment;
  
  /**
   * The payment object to be injected to this controller
   */
  public $payment;
  
  /**
   * Message to show when payment is completed
   * TODO: use template
   */ 
  public $complete_message = "Payment is completed";
  
  /**
   * Return the relative url for completing payment 
   */
  public function complete_link() {
    // Bypass the inheritance limitation
    $class = get_class($this);
    return $class::$URLSegment . '/complete';
  }
  
  /**
   * Return the relative url for cancelling payment
   */
  public function cancel_link() {
    // Bypass the inheritance limitation
    $class = get_class($this);
    return $class::$URLSegment . '/cancel';
  }
  
  /**
   * Construct a controller with an injected payment object
   */
  public static function initWithPayment($payment) {
    // construct the correct child class
    $class = get_called_class();
    $instance = new $class();
    
    $instance->payment = $payment;
    return $instance;
  }
  
  /**
   * Get the controller's class name from a given gateway module name and config
   * TODO: Generalize naming convention; make use of _config.php
   * 
   * @param $gatewayName
   * @return Controller class name
   */
  public static function controllerClassName($gatewayName) {
    $paymentClass = Payment::gatewayClassName($gatewayName);
    $controllerClass = $paymentClass . "_Controller";
    
    if (class_exists($controllerClass)) {
      return $controllerClass;
    } else {
      user_error("Controller class is not defined", E_USER_ERROR);
    } 
  }
  
  /**
   * Process a payment from an order form. 
   * TODO: If this function becomes unneccesseary, omit it.  
   * 
   * @param $data
   */
  public function processPayment($data) {
    if (! isset($data['Amount'])) {
      user_error("Payment amount not set!", E_USER_ERROR);
    } else {
      $amount = $data['Amount'];
    }
    
    if (! isset($data['Currency'])) {
      $currency = $data['Currency'];
    } else {
      $currency = $this->payment->site_currency();
    }
    
    // Save preliminary data to database
    $this->payment->Amount->Amount = $amount;
    $this->payment->Amount->Currency = $currency;
    $this->payment->Status = 'Pending';
    $this->payment->write();
    
    // Process payment
    $this->processRequest($data);
  }
  
  /**
   * Process a payment request, to be implemented by specific gateways 
   * 
   * @param $data
   * @return Payment_Result
   */
  public function processRequest($data) {
    user_error("Please implement processRequest() on $this->class", E_USER_ERROR);
  }
  
  /**
   * Process a payment response, to be implemented by specific gateways.
   * This function should return the ID of the payment  
   */
  public function processResponse($response) {
    user_error("Please implement processResponse() on $this->class", E_USER_ERROR);
  }
  
  /**
   * Get the payment ID from the gateway response. To be implemented by specific gateways
   */
  public function getPaymentID($response) {
    user_error("Please implement getPaymentID() on $this->class", E_USER_ERROR);
  }
  
  /**
   * Payment complete handler. 
   * This function should be persistent accross all payment gateways.
   * Additional processing of payment response can be done in processResponse(). 
   */
  public function complete($request) {
    // Additional processing
    $this->processResponse($request);    
    
    $paymentID = $this->getPaymentID($request);    
    if ($payment = DataObject::get_by_id('Payment', $paymentID)) {
      $payment->Status = 'Complete';
      $payment->write();
    } else {
      user_error("Cannot load the corresponding payment of id $paymentID", E_USER_ERROR);
    }
    
    print($this->complete_message);
  }
  
  /**
   * Payment cancel handler
   */
  public function cancel($request) {
    // Additional processing
    $this->processResponse($response);

    $paymentID = $this->getPaymentID($request);
    if ($payment = DataObject::get_by_id('Payment', $paymentID)) {
      $payment->Status = 'Incomplete';
      $payment->write();
    } else {
      user_error("Cannot load the corresponding payment of id $paymentID", E_USER_ERROR);
    }
    
    // Do something...
  }
}