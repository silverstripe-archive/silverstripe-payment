<?php 

class Dummy_Gateway extends Payment_Gateway {
  
  public function process($data) { 
    Session::set('amount', $data['Amount']);
    Director::redirect($this->returnURL);
  }
  
  public function getResponse($response) {
    $amount = Session::get('amount');
    $cents = round($amount - intval($amount), 2);
    switch ($cents) {
      case 0.01:
        return new Gateway_Result(Gateway_Result::SUCCESS);
        break;
      case 0.02:
        return new Gateway_Result(Gateway_Result::FAILURE);
        break;
      case 0.03:
        return new Gateway_Result(Gateway_Result::INCOMPLETE);
        break;
      default:
        return new Gateway_Result();
        break;
    }
  }
}

class Dummy_Production_Gateway extends Dummy_Gateway { }