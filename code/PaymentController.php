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
   * Process a payment request
   * 
   * @param $data
   *   The payment data to be processed
   * @return
   */
  public function processRequest($data) {
    
  }
}