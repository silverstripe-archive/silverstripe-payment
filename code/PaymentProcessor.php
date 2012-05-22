<?php

/**
 * Static class for processing payment. 
 * 
 * @package payment
 */
class PaymentProcessor {
  public $paymentController;
  
  /**
   * Inject the corresponding payment controller
   */
  public function __construct($paymentController) {
    $this->paymentController = $paymentController;
  }
  
  /**
   * Process payment using the injected payment controller
   * 
   * @param $data
   *   The payment data 
   * @return PaymentProcessorResult
   */
  public function processPayment($data) {
    $processResult = $this->paymentController->processRequest($data);
    
    switch($processResult) {
      
    }
  }
}