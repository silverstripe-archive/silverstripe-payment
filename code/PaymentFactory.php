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
    if ($paymentClassName = Payment::gatewayClassName($gatewayname)) {
      $payment = new $paymentClassName();
      $paymentController = new PaymentController($payment);
      $this->paymentProcessor = new PaymentProcessor($paymentController);
    } else {
      // Payment class is not yet defined
    }
  }
}