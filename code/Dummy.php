<?php

/**
 * Object representing a dummy payment gateway
 */
class DummyMerchantHosted extends Payment { }

class DummyGatewayHosted extends Payment { }

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

class DummyMerchantHosted_Gateway_Production extends DummyMerchantHosted_Gateway { }

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

class DummyGatewayHosted_Gateway_Production extends DummyGatewayHosted_Gateway { }