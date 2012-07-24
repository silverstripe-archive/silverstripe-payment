<?php

class ValidationTest extends SapphireTest {
  
  public $processor;
  
  function setUp() {
    parent::setUp();
    
    $this->processor = PaymentFactory::factory('DummyMerchantHosted');
  }
  
  function testAmoutNotSet() {
    $data = array('Currency' => 'USD');
    $this->processor->gateway->validatePaymentData($data);
    $this->assertEquals($this->processor->gateway->validationResult->message(), 'Payment amount not set');
  }
  
  function testAmountNull() {
    $data = array('Amount' => '', 'Currency' => 'USD');
    $this->processor->gateway->validatePaymentData($data);
    $this->assertEquals($this->processor->gateway->validationResult->message(), 'Payment amount cannot be null');
  }
  
  function testCurrencyNotSet() {
    $data = array('Amount' => '10');
    $this->processor->gateway->validatePaymentData($data);
    $this->assertEquals($this->processor->gateway->validationResult->message(), 'Payment currency not set');
  }
  
  function testCurrencyNull() {
    $data = array('Amount' => '10', 'Currency' => '');
    $this->processor->gateway->validatePaymentData($data);
    $this->assertEquals($this->processor->gateway->validationResult->message(), 'Payment currency cannot be null');
  }
  
  function testCurrencyNotSupported() {
    $data = array('Amount' => '10', 'Currency' => 'AUD');
    $this->processor->gateway->validatePaymentData($data);
    $this->assertEquals($this->processor->gateway->validationResult->message(), 'Currency AUD not supported by this gateway');
  }
}