<?php

class PayPalDirectTest extends PayPalTest {

  public $processor;
  public $data;

  function setUp() {
    parent::setUp();
    
    Config::inst()->remove('PaymentGateway', 'environment');
    Config::inst()->update('PaymentGateway', 'environment', 'dev');

    $this->processor = PaymentFactory::factory('PayPalDirect');
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
  }

  function testClassConfig() {
    $this->assertEquals(get_class($this->processor), 'PaymentProcessor_MerchantHosted');
    $this->assertEquals(get_class($this->processor->gateway), 'PayPalDirectGateway');
    $this->assertEquals(get_class($this->processor->payment), 'PayPal');
  }

  function testPaymentSuccess() {
    $response = $this->processor->gateway->process($this->data);
    $result = $this->processor->gateway->getResponse($response);
    $this->assertEquals($result->getStatus(), PaymentGateway_Result::SUCCESS);
  }

  function testPaymentFailure() {
    $this->data['Currency'] = 'xxx';
    $response = $this->processor->gateway->process($this->data);
    $result = $this->processor->gateway->getResponse($response);
    $this->assertEquals($result->getStatus(), PaymentGateway_Result::FAILURE);  
  }
}

class PayPalDirectMockTest extends PayPalTest {
  
  function setUp() {
    parent::setUp();
    
    Config::inst()->remove('PaymentGateway', 'environment');
    Config::inst()->update('PaymentGateway', 'environment', 'test');
    
    $this->processor = PaymentFactory::factory('PayPalDirect');
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
  }
  
  function testClassConfig() {
    $this->assertEquals(get_class($this->processor), 'PaymentProcessor_MerchantHosted');
    $this->assertEquals(get_class($this->processor->gateway), 'PayPalDirectGateway_Mock');
    $this->assertEquals(get_class($this->processor->payment), 'PayPal');
  }
  
  function testPayPalDirectMockSuccess() {
    $response = $this->processor->gateway->process($this->data);
    $result = $this->processor->gateway->getResponse($response);
    $this->assertEquals($result->getStatus(), PaymentGateway_Result::SUCCESS);
  }
  
  function testPayPalDirectMockFailure() {
    $this->data['Amount'] = '10.01';
    $response = $this->processor->gateway->process($this->data);
    $result = $this->processor->gateway->getResponse($response);
    $this->assertEquals($result->getStatus(), PaymentGateway_Result::FAILURE);
  }
}