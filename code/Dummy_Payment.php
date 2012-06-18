<?php

/**
 * Object representing a dummy payment gateway
 */
class Dummy_Payment extends Payment {
   
}

/**
 * Controller for DummyPayment
*/
class Dummy_Controller extends Payment_Controller {

  static $URLSegment = 'dummy';
  
  public function getFormFields() {
    $fieldList = new FieldList();
    $fieldList->push(new NumericField('Amount', 'Amount', '10.00'));
    $fieldList->push(new TextField('Currency', 'Currency', 'NZD'));
    
    return $fieldList;
  }

  public function processRequest($data) {
    return parent::processRequest($data);
  }

  public function processResponse($response) {
    // Nothing to do here...
  }
}