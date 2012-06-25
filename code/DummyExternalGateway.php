<?php

/**
 * Imaginary place to visit external gateway
 */
class DummyExternalGateway_Controller extends Controller{

  function pay($request) {
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

    return new Form($this, "PayForm", $fields, $actions);
  }

  function dopay() {
    $data = Session::get('paymentData');
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
    
    Controller::redirect($data['returnURL']);
  }
}