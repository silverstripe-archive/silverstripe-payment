<?php

/**
 * Controller for DummyPayment
 */
class Dummy_Payment_Controller extends Payment_Controller {
  
  static $URLSegment = 'dummy';
  
  protected static $test_mode = 'success';

  static function set_test_mode($test_mode) {
    if ($test_mode == 'success' || $test_mode == 'cancel') {
      self::$test_mode = $test_mode;
    } else {
      user_error('Test mode not supported', E_USER_ERROR);
    }
  }
  
  /**
   * Override to add payment id to the link
   * @see Payment_Controller::complete_link()
   */
  public function complete_link() {
    return self::$URLSegment . '/complete/' . $this->payment->ID;
  }
  
  /**
   * @see Payment_Controller::cancel_link()
   */
  public function cancel_link() {
    return self::$URLSegment . '/cancel/' . $this->payment->ID;
  }
  
  public function processRequest($data) {
    // Redirect to the return url
    switch (self::$test_mode) {
      case 'success':
        Director::redirect(Director::absoluteBaseURL() . $this->complete_link());
        break;
      case 'cancel':
        Director::redirect(Director::absoluteBaseURL() . $this->cancel_link());
    }    
  }
  
  public function processResponse($response) {
    // Nothing to do here...
  }
  
  public function getPaymentID($response) {
    return $response->param('ID');
  }
}