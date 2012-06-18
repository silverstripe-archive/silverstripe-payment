<?php

/**
 * Object representing a dummy payment gateway
 */
class Dummy_Payment extends Payment { }

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
