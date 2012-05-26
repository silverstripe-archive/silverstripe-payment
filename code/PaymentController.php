<?php

/**
 * Abstract class for a number of payment controllers. 
 * 
 * @package payment
 */
class PaymentController extends Controller {
  public $payment;
  
  /**
   * Inject the corresponding payment object to this controller
   */
  public function __construct($payment) {
    $this->payment = $payment;
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
}