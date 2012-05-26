<?php

/**
 * Controller for DummyPayment
 */
class DummyPaymentController extends PaymentController {
  /**
   * Process the dummy payment method
   */
  function processRequest($data) {
    return new Payment_Success();
  }
}