<?php

/**
 * Factory class to allow easy initiation of payment objects
 */
class PaymentFactory {
  public $paymentProcessor;
  
  /**
   * Construct an instance of this object given the name of the desired payment gateway
   */
  public function __construct($gatewayName) {
    $paymentClass = Payment::gatewayClassName($gatewayname);
    $payment = new $paymentClass();
    $paymentControllerClass = PaymentController::controllerClassName($gatewayName);
    $paymentController = new $paymentControllerClass();
    
    $this->paymentProcessor = new PaymentProcessor($paymentController);
  }
}