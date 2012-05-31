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
    // Save preliminary data to database
    $this->payment->Status = 'Pending';
    $this->payment->write();
    
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
   * Process a payment response, to be implemented by specific gateways 
   */
  public function processResponse($response) {
    user_error("Please implement processRequest() on $this->class", E_USER_ERROR);
  }
  
  /**
   * Payment complete handler
   */
  public function complete() {
    // Load the payment object from database
    //$this->payment->Status = 'Completed';
    //$this->payment->write();
    print($this->complete_message);
  }
  
  /**
   * Payment cancel handler
   */
  public function cancel() {
    $this->payment->Status = 'Cancelled';
    $this->payment->write();
  }
}