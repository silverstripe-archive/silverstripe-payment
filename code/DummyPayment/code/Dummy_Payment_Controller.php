<?php

/**
 * Controller for DummyPayment
 */
class Dummy_Payment_Controller extends PaymentController {
  
  static $URLSegment = 'dummy';
  
  /**
   * Process the dummy payment method
   */
  function processRequest($data) {
    // Redirect to the return url
    
    return new Payment_Success();
  }
}