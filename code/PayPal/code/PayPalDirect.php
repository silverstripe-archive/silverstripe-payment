<?php

/**
 * Implementation of PayPalDirectPayment
 */
class PayPalDirectGateway extends PayPalGateway {
  
  public function validatePaymentData($data) {
    $result = parent::validatePaymentData($data);
    
    if (! isset($data['CreditCard'])) {
      $result->error('Credit card details are not available');
    }
    
    $creditCardValidation = $data['CreditCard']->validate();
    if (! $creditCardValidation->valid()) {
      foreach ($creditCardValidation->messageList() as $error) {
        $result->error($error);
      }
    }
    
    return $result;
  }

  public function process($data) {
    parent::process($data);

    $this->postData['METHOD'] = 'DoDirectPayment';
    // Add credit card data. May have to parse the data to fit PayPal's format
    $creditCard = $data['CreditCard'];
    $this->postData['CREDITCARDTYPE'] = $creditCard->type;
    $this->postData['ACCT'] = $creditCard->number;
    $this->postData['EXPDATE'] = $creditCard->month . $creditCard->year;
    //$this->postData['CVV2'] = $data['Cvv2'];
    $this->postData['FIRSTNAME'] = $creditCard->firstName;
    $this->postData['LASTNAME'] = $creditCard->lastName;

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
        return new PaymentGateway_Result();
        break;
      case self::FAILURE_CODE:
        return new PaymentGateway_Result(false);
        break;
      default:
        return new PaymentGateway_Result(false);
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