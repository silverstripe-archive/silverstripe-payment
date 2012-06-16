<?php

/**
 * Object representing a dummy payment gateway
 */
class Dummy_Payment extends Payment {
   
}

/**
 * Controller for DummyPayment
*/
class Dummy_Payment_Controller extends Payment_Controller {

  static $URLSegment = 'dummy';

  public function processRequest($data) {
    parent::processRequest($data);
  }

  public function processResponse($response) {
    // Nothing to do here...
  }
}