<?php

class OrderDemoPage_Controller extends Page_Controller {

  function orderForm() {
    $fields = new FieldSet(
      new HeaderField("Enter your details", 4),
      new TextField("FirstName", "First Name"),
      new TextField("Surname", "Last Name"),
      new EmailField("Email", "Email"),
      new TextField("Street", "Street"),
      new TextField("Suburb", "Suburb"),
      new TextField("CityTown", "City / Town"),
      new TextField("Country", "Country")
    );
    
    $actions = new FieldSet(
      new FormAction('processOrder', 'Place order')
    );
    
    return new Form($this, 'OrderForm', $fields, $actions);
  }
  
  function processOrder($data, $form) {
    // Dummy payment data for testing
    $paymentData = array(
      'amount' => '10',
      'currency' => 'USD',
    );
    
    $paymentProcessor = PaymentFactory::createProcessor('Dummy');
    $paymentProcessor->processPayment($paymentData, $form);
  }
  
  function placeOrder() {
    $form = $this->orderForm();
    $customisedController = $this->customise(array(
			"Content" => $form->forTemplate(),
			"Form" => '',
		));
		
		return $customisedController->renderWith("Page");
  }
}