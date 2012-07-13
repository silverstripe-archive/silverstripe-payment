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

class PayPalDirectGateway_Mock extends PayPalDirectGateway {
  
  /**
   * The response template string for PayPalDirect
   * 
   * @var String
   */
  private $responseTemplate = 'TIMESTAMP=&CORRELATIONID=&ACK=&VERSION=&BUILD=&AMT=&CURRENCYCODE=&AVSCODE=&CVV2MATCH=&TRANSACTIONID=';
  
  public function paymentDataRequirements() {
    return array('Amount', 'Currency');
  }
  
  /**
   * Generate a dummy NVP response string with some pre-set value
   * 
   * @param $data - The payment data; $ack - The desired ACK code, default to Success
   * 
   *  @return the dummy response NVP string 
   */
  public function genrateDummyResponse($data, $ack = null) {
    $templateArr = $this->parseResponse($this->responseTemplate);
    $templateArr['TIMESTAMP'] = time();
    $templateArr['CORRELATIONID'] = 'cfcb59afaabb4';
    $templateArr['ACK'] = (isset($ack)) ? $ack : 'Success';
    $templateArr['VERSION'] = self::PAYPAL_VERSION;
    $templateArr['BUILD'] = '1195961';
    $templateArr['AMT'] = $data['Amount'];
    $templateArr['CURRENCYCODE'] = $data['Currency'];
    $templateArr['AVSCODE'] = 'X';
    $templateArr['CVV2MATCH'] = 'M';
    $templateArr['TRANSACTIONID'] = '1000';
    
    return http_build_query($templateArr);
  }
  
  public function process($data) {
    $amount = $data['Amount'];
    $cents = round($amount - intval($amount), 2);
    
    switch ($cents) {
      case 0.00:
        return $this->genrateDummyResponse($data, 'Success');
        break;
      case 0.01:
        return $this->genrateDummyResponse($data, 'Failure');
        break;
      default:
        return $this->genrateDummyResponse($data, 'Failure');
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