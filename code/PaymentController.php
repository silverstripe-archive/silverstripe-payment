<?php

/**
 * Abstract class for a number of payment controllers. 
 * 
 * @package payment
 */
class PaymentController extends Controller {
  
  static $URLSegment;
  
  static function complete_link() {
    return self::$URLSegment . '/complete';
  }
  
  static function cancel_link($custom) {
    return self::complete_link() . '?custom=' . $custom;
  }
  
  /**
   * The payment object to be injected to this controller
   */
  public $payment;
  
  /**
   * Inject the corresponding payment object to this controller
   */
  public function __construct($payment) {
    $this->payment = $payment;
  }
  
  /**
   * Get the controller's class name from a given gateway module name and config
   * TODO: Generalize naming convention; make use of _config.php
   * 
   * @param $gatewayName
   * @return Controller class name
   */
  public static function controllerClassName() {
    $paymentClass = Payment::gatewayClassName($gatewayname);
    $controllerClass = $paymentClass . "_Controller";
    
    if (class_exists($controllerClass)) {
      return $controllerClass;
    } else {
      user_error("Controller class is not defined", E_USER_ERROR);
    } 
  }
  
  /**
   * Process a payment from an order form.  
   * 
   * @param $data, $form
   */
  public function processPayment($data, $form) {
    $this->payment->Status = 'Pending';
    $this->payment->write();
    
    // Overwrite if further form processing is needed
    processRequest($data);
  }
  
  /**
   * Process a payment request. Each gateway must implement this function. 
   * 
   * @param $data
   * @return Payment_Result
   */
  public function processRequest($data) {
    user_error("Please implement processRequest() on $this->class", E_USER_ERROR);
  }
  
  /**
   * Process a payment response. 
   */
  public function processResponse($response) {
    
  }
}