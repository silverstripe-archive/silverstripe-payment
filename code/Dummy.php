<?php

/**
 * Object representing a dummy payment gateway
 */
class Dummy extends Payment { }

/**
 * Imaginary place to visit external gateway
 */
class Dummy_ExternalGateway_Controller extends Controller{

  function pay($request) {
    Session::set('returnurl', $request->requesetVar("returnurl"));
    return array(
      'Content' => "Fill out this form to make payment",
      'Form' => $this->PayForm()
    );
  }

  function PayForm() {
    $fields = new FieldList(
      new TextField("name"),
      new CreditCardField("creditcard"),
      new DateField("issued"),
      new DateField("expiry")
    );
    
    $actions = new FieldList(
        new FormAction("dopay")
    );
    
    return Form($this, "PayForm", $fields, $actions);
  }

  function dopay() {
    Director::redirect(Session::get('returnurl')); 
  }
} 

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