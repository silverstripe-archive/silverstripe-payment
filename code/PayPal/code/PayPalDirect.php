<?php

/**
 * Implementation of PayPalDirectPayment
 */
class PayPalDirectGateway extends PayPalGateway {
  
  public function paymentDataRequirements() {
    return array('CreditCardType', 'CardNumber', 'Cvv2', 'DateExpiry', 'FirstName', 'LastName');
  }

  public function process($data) {
    parent::process($data);

    $this->postData['METHOD'] = 'DoDirectPayment';
    // Add credit card data. May have to parse the data to fit PayPal's format
    $this->postData['CREDITCARDTYPE'] = $data['CreditCardType'];
    $this->postData['ACCT'] = $data['CardNumber'];
    $this->postData['EXPDATE'] = $data['DateExpiry'];
    $this->postData['CVV2'] = $data['Cvv2'];
    $this->postData['FIRSTNAME'] = $data['FirstName'];
    $this->postData['LASTNAME'] = $data['LastName'];

    // Add optional parameters 
    $this->postData['IP'] = isset($data['IP']) ? $data['IP'] : $_SERVER['REMOTE_ADDR'];

    // Post the data to PayPal server
    return $this->postPaymentData($this->postData);
  }

  public function getResponse($response) {
    $responseArr = $this->parseResponse($response);

    switch ($responseArr['ACK']) {
      case self::SUCCESS_CODE:
      case self::SUCCESS_WARNING:
        return new PaymentGateway_Result(PaymentGateway_Result::SUCCESS);
        break;
      case self::FAILURE_CODE:
        return new PaymentGateway_Result(PaymentGateway_Result::FAILURE);
        break;
      default:
        return new PaymentGateway_Result();
        break;
    }
  }
}

class PayPalDirectProcessor extends PaymentProcessor_MerchantHosted {
  
  public function getFormFields() {
    $fieldList = parent::getFormFields();
    
    $fieldList->insertBefore(new DropDownField(
      'CreditCardType', 
      'Select Credit Card Type', 
      array('visa' => 'Visa', 'master' => 'Master')
    ), 'CardNumber');
    $fieldList->insertBefore(new TextField('FirstName', 'First Name:'), 'CardNumber');
    $fieldList->insertBefore(new TextField('LastName', 'Last Name:'), 'CardNumber');
    
    return $fieldList;
  }
}