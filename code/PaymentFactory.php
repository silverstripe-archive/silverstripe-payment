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
    $paymentClass = Payment::gatewayClassName($gatewayName);
    $payment = new $paymentClass();
    $paymentControllerClass = Payment_Controller::controllerClassName($gatewayName);
    $paymentController = $paymentControllerClass::initWithPayment($payment);
    
    return new PaymentProcessor($paymentController);
  }
}