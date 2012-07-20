<?php

/**
 * Test class for merchant-hosted payment
 */
class DummyMerchantHostedGateway extends PaymentGateway {
  
  public function getSupportedCreditCardType() {
    return (array('master' => 'Master', 'visa' => 'Visa'));
  }

  public function process($data) {
    $amount = $data['Amount'];
    $cents = round($amount - intval($amount), 2);
    
    switch ($cents) {
      case 0.00:
        return new PaymentGateway_Result();
        break;
      case 0.01:
        return new PaymentGateway_Result(false);
        break;
      case 0.02:
        $result = new PaymentGateway_Result();
        $result->setStatus(PaymentGateway_Result::INCOMPLETE);
        return $result;
        break;
      default:
        return new PaymentGateway_Result(false);
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
class DummyGatewayHostedGateway extends PaymentGateway {
  
  public function __construct() {
    $this->gatewayURL = Director::baseURL() . 'dummy/external/pay';
  }

  public function process($data) {
    $postData = array(
      'Amount' => $data['Amount'],
      'Currency' => $data['Currency'],
      'ReturnURL' => $this->returnURL    
    ); 
    
    $queryString = http_build_query($postData);
    Controller::curr()->redirect($this->gatewayURL . '?' . $queryString);
  }

  public function getResponse($response) {
    $result = $response->getVar('result');
    switch ($result) {
      case PaymentGateway_Result::SUCCESS:
        return new PaymentGateway_Result();
        break;
      case PaymentGateway_Result::FAILURE:
        return new PaymentGateway_Result(false);
        break;
      case PaymentGateway_Result::INCOMPLETE:
        $result = new PaymentGateway_Result();
        $result->setStatus(PaymentGateway_Result::INCOMPLETE);
        return $result;
      default:
        return new PaymentGateway_Result(false);
        break;
    }
  }
}

/**
 * Imaginary place to visit external gateway
 */
class DummyExternalGateway_Controller extends Controller{

  function pay($request) {
    Session::set('Amount', $request->getVar('Amount'));
    Session::set('Currency', $request->getVar('Currenct'));
    Session::set('ReturnURL', $request->getVar('ReturnURL'));
    
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
    if ($amount = Session::get('Amount')) {
      $amount = $amount;
      $cents = round($amount - intval($amount), 2);

      switch ($cents) {
        case 0.00:
          $result = PaymentGateway_Result::SUCCESS;
          break;
        case 0.01:
          $result = PaymentGateway_Result::FAILURE;
          break;
        case 0.02:
          $result = PaymentGateway_Result::INCOMPLETE;
          break;
        default:
          $result = PaymentGateway_Result::FAILURE;
          break;
      }
      if ($returnURL = Session::get('ReturnURL')) {
        Controller::redirect($returnURL . "?result=$result");
      } else {
        user_error("Return URL is not set for this transaction", E_USER_ERROR);
      }
    } else {
      user_error("Payment data is invalid for this transaction", E_USER_ERROR);
    }
  }
}