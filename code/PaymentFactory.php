<?php

/**
 * Factory class to allow easy initiation of payment objects
 */
class PaymentFactory {
  /**
   * Construct an instance of this object given the name of the desired payment gateway
   * 
   * @param $gatewayName
   * @return PaymentProcessor
   */
  public static function createProcessor($gatewayName) {
    $paymentClass = Payment::gatewayClassName($gatewayname);
    $payment = new $paymentClass();
    $paymentControllerClass = PaymentController::controllerClassName($gatewayName);
    $paymentController = new $paymentControllerClass();
    
    return new PaymentProcessor($paymentController);
  }
}