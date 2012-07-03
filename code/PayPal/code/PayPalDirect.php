<?php

/**
 * Implementation of PayPalDirectPayment
 */
class PayPalDirect_Gateway extends PayPal_Gateway {

  public function process($data) {
    parent::process($data);

    $this->postData['METHOD'] = 'DoDirectPayment';
    // Add credit card data. May have to parse the data to fit PayPal's format
    $this->postData['ACCT'] = $data['CardNumber'];
    $this->postData['EXPDATE'] = $data['DateExpiry'];
    $this->postData['CVV2'] = $data['Cvc2'];

    // And billing information as well

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