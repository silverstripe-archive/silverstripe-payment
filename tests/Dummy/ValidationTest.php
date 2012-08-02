<?php

class ValidationTest extends SapphireTest {
  
  public $processor;
  
  function setUp() {
    parent::setUp();
    
    $this->processor = PaymentFactory::factory('DummyMerchantHosted');
  }
  
  function testAmoutNotSet() {
    $data = array('Currency' => 'USD');
    $result = $this->processor->gateway->validatePaymentData($data);
    $this->assertEquals($result->message(), 'Payment amount not set');
  }
  
  function testAmountNull() {
    $data = array('Amount' => '', 'Currency' => 'USD');
    $result = $this->processor->gateway->validatePaymentData($data);
    $this->assertEquals($result->message(), 'Payment amount cannot be null');
  }
  
  function testCurrencyNotSet() {
    $data = array('Amount' => '10');
    $result = $this->processor->gateway->validatePaymentData($data);
    $this->assertEquals($result->message(), 'Payment currency not set');
  }
  
  function testCurrencyNull() {
    $data = array('Amount' => '10', 'Currency' => '');
    $result = $this->processor->gateway->validatePaymentData($data);
    $this->assertEquals($result->message(), 'Payment currency cannot be null');
  }
  
  function testCurrencyNotSupported() {
    $data = array('Amount' => '10', 'Currency' => 'AUD');
    $result = $this->processor->gateway->validatePaymentData($data);
    $this->assertEquals($result->message(), 'Currency AUD not supported by this gateway');
  }
}