<?php

/**
 * Controller for DummyPayment
 */
class Dummy_Payment_Controller extends PaymentController {
  /**
   * Process the dummy payment method
   */
  function processRequest($data) {
    return new Payment_Success();
  }
}