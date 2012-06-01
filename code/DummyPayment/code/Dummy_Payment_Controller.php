<?php

/**
 * Controller for DummyPayment
 */
class Dummy_Payment_Controller extends Payment_Controller {
  
  static $URLSegment = 'dummy';
  
  /**
   * Override to add payment id to the link
   * @see Payment_Controller::complete_link()
   */
  public function complete_link() {
    return self::$URLSegment . '/complete/' . $this->payment->ID;
  }
  
  public function processRequest($data) {
    // Redirect to the return url
    // TODO: Allow cancelled and failed payment as well for testing purposes
    Director::redirect(Director::absoluteBaseURL() . $this->complete_link());
  }
  
  public function processResponse($response) {
    // Nothing to do here
  }
  
  public function getPaymentID($response) {
    return $response->param('ID');
  }
}