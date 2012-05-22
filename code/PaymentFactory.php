<?php

/**
 * Factory class to allow easy initiation of payment objects
 */
class PaymentManager {
  public $paymentProcessor;
  
  /**
   * Construct an instance of this object given the name of the desired payment gateway
   */
  public function __construct($gatewayName) {
    $payment = new Payment($gatewayName);
    $paymentController = new PaymentController($payment);
    $this->paymentProcessor = new PaymentProcessor($paymentController);
  }
  
  /**
   * Process payment using the given gateway
   * 
   *  @param $data
   *    The payment data to be processed
   *  @return
   */
  public function processPayment($data) {
    $result = $this->paymentProcessor->processPayment($data);
    
    switch($result) {
      
    }
  }
}