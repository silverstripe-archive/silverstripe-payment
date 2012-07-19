<?php

class DummyMerchantHostedTest extends SapphireTest {
  
  public $data;
  public $processor;
  
  function setUp() {
    parent::setUp();
    
    $this->data = array(
      'Amount' => '10',
      'Currency' => 'USD',
      'FirstName' => 'Ryan',
      'LastName' => 'Dao',
      'CreditCardType' => 'master',
      'CardNumber' => '4381258770269608',
      'DateExpiry' => '11-2016',
      'Cvc2' => '146'  
    );
    
    $this->processor = PaymentFactory::factory('DummyMerchantHosted');
  }
  
  function testClassConfig() {
    $processor = PaymentFactory::factory('DummyMerchantHosted');
    $this->assertEquals(get_class($processor), 'PaymentProcessor_MerchantHosted');
    $this->assertEquals(get_class($processor->gateway), 'DummyMerchantHostedGateway');
    $this->assertEquals(get_class($processor->payment), 'Payment');
  }
  
  function testPaymentSuccess() {
    $this->processor->processRequest($this->data);
    $this->assertEquals($this->processor->payment->Status, Payment::SUCCESS);
  }
  
  function testPaymentFailure() {
    $this->data['Amount'] = '10.01';
    $this->processor->processRequest($this->data);
    $this->assertEquals($this->processor->payment->Status, Payment::FAILURE);
  }
  
  function testPaymentIncomplete() {
    $this->data['Amount'] = '10.02';
    $this->processor->processRequest($this->data);
    $this->assertEquals($this->processor->payment->Status, Payment::INCOMPLETE);
  }  
}

class DummyGatewayHostedTest extends FunctionalTest {
  
  /* Black-box testing */
  function testClassConfig() {
    $controller = PaymentFactory::factory('DummyGatewayHosted');
    $this->assertEquals(get_class($controller), 'PaymentProcessor_GatewayHosted');
    $this->assertEquals(get_class($controller->gateway), 'DummyGatewayHostedGateway');
    $this->assertEquals(get_class($controller->payment), 'Payment');
  }
  
  function testPaymentFlow() {
    
  }
}