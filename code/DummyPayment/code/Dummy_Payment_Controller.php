<?php

/**
 * Controller for DummyPayment
 */
class Dummy_Payment_Controller extends Payment_Controller {
  
  static $URLSegment = 'dummy';
  
  /**
   * Process the dummy payment method
   */
  function processRequest($data) {
    // Redirect to the return url
    // TODO: Allow cancelled and failed payment as well for testing purposes
    Director::redirect(complete_link());
  }
}