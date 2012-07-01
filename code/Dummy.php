<?php

/**
 * Test class for merchant-hosted payment
 */
class DummyMerchantHosted_Gateway extends Payment_Gateway {

  public function process($data) {
    $amount = $data['Amount'];
    $cents = round($amount - intval($amount), 2);
    
    switch ($cents) {
      case 0.00:
        return new Payment_Gateway_Result(Payment_Gateway_Result::SUCCESS);
        break;
      case 0.01:
        return new Payment_Gateway_Result(Payment_Gateway_Result::FAILURE);
        break;
      case 0.02:
        return new Payment_Gateway_Result(Payment_Gateway_Result::INCOMPLETE);
        break;
      default:
        return new Payment_Gateway_Result();
        break;
    }
  }

  public function getResponse($response) {
    return $response;
  }
}

/**
 * Test class for gateway-hosted payment
 */
class DummyGatewayHosted_Gateway extends Payment_Gateway {
  
  public function __construct() {
    $this->gatewayURL = Director::baseURL() . 'dummy/external/pay';
  }

  public function process($data) {
    $postData = array(
      'Amount' => $data['Amount'],
      'Currency' => $data['Currency'],
      'ReturnURL' => $this->returnURL    
    ); 
    
    // TODO: Use curl and GET instead of Session
    Session::set('paymentData', $postData);
    Controller::curr()->redirect($this->gatewayURL);
  }

  public function getResponse($response) {
    switch (Session::get('result')) {
      case Payment_Gateway_Result::SUCCESS:
        return new Payment_Gateway_Result(Payment_Gateway_Result::SUCCESS);
        break;
      case Payment_Gateway_Result::FAILURE:
        return new Payment_Gateway_Result(Payment_Gateway_Result::FAILURE);
        break;
      case Payment_Gateway_Result::INCOMPLETE:
        return Payment_Gateway_Result(Payment_Gateway_Result::INCOMPLETE);
      default:
        return new Payment_Gateway_Result();
        break;
    }
  }
}

/**
 * Imaginary place to visit external gateway
 */
class DummyExternalGateway_Controller extends Controller{

  function pay($request) {
    return $this->customise(array(
        'Content' => "<h1>Fill out this form to make payment</h1>",
        'Form' => $this->PayForm()
    ))->renderWith('Page');
  }

  function PayForm() {
    $fields = new FieldList(
        new TextField('CardHolderName', 'Card Holder Name'),
        new CreditCardField('CardNumber', 'Card Number'),
        new DateField('DateExpiry', 'Expiration date')
    );

    $actions = new FieldList(
        new FormAction("dopay")
    );

    return new Form($this, "PayForm", $fields, $actions);
  }

  function dopay() {
    if ($data = Session::get('paymentData')) {
      $amount = $data['Amount'];
      $cents = round($amount - intval($amount), 2);

      switch ($cents) {
        case 0.00:
          Session::set('result', Payment_Gateway_Result::SUCCESS);
          break;
        case 0.01:
          Session::set('result', Payment_Gateway_Result::FAILURE);
          break;
        case 0.02:
          Session::set('result', Payment_Gateway_Result::INCOMPLETE);
          break;
        default:
          Session::set('result', Payment_Gateway_Result::FAILURE);
          break;
      }

      Controller::redirect($data['ReturnURL']);
    } else {
      user_error("No user data is set for this transaction", E_USER_ERROR);
    }
  }
}