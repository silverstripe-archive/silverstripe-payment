<?php

/**
 * Implementation of PayPalExpressCheckout
 */
class PayPalExpressGateway extends PayPalGateway {
  /**
   * The PayPal token for this transaction
   *
   * @var String
   */
  private $token = null;

  public function process($data) {
    parent::process($data);

    $this->setExpressCheckout();
    if ($$this->token) {
      // If Authorization successful, redirect to PayPal to complete the payment
      Controller::curr()->redirect(self::get_paypal_redirect_url() . "?cmd=_express-checkout&token=$token");
    } else {
      // Otherwise, do something...
    }
  }
  
  /**
   * Send a request to PayPal to authorize an express checkout transaction
   */
  public function setExpressCheckout() {
    $this->postData['METHOD'] = 'SetExpressCheckout';
    // Add return and cancel urls
    $this->postData['RETURNURL'] = $this->returnURL;
    $this->postData['CANCELURL'] = Director::absoluteBaseURL();
    
    // Post the data to PayPal server to get the token
    $response = $this->parseResponse($this->postPaymentData($this->postData));
    
    if ($token = $response['TOKEN']) {
      $this->token = $token;
    }
    
    return $token;
  }

  public function getResponse($response) {
    // Get the payer information
    $this->preparePayPalPost();
    $this->postData['METHOD'] = 'GetExpressCheckoutDetails';
    $this->postData['TOKEN'] = $this->token;
    $response = $this->parseResponse($this->postPaymentData($this->data));

    // If successful, complete the payment
    if ($response['ACK'] == self::SUCCESS_CODE || $response['ACK'] == self::SUCCESS_WARNING) {
      $payerID = $response['PAYERID'];

      $this->preparePayPalPost();
      $this->postData['METHOD'] = 'DoExpressCheckoutPayment';
      $this->postData['PAYERID'] = $payerID;
      $this->postData['PAYMENTREQUEST_0_PAYMENTACTION'] = (self::get_action());
      $this->postData['TOKEN'] = ($this->token);
      $response = $this->parseResponse($this->postPaymentData($this->data));

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
}
