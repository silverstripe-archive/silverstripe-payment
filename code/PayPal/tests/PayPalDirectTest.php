<?php

class PayPalDirectTest extends SapphireTest {

  public $processor;
  public $data;

  function setUp() {
    parent::setUp();
    
    $paymentMethods = array('PayPalDirect');
    Config::inst()->remove('PaymentProcessor', 'supported_methods');
    Config::inst()->update('PaymentProcessor', 'supported_methods', $paymentMethods);
    
    Config::inst()->remove('PaymentGateway', 'environment');
    Config::inst()->update('PaymentGateway', 'environment', 'dev');

    $this->processor = PaymentFactory::factory('PayPalDirect');
    $this->data = array(
      'Amount' => '10',
      'Currency' => 'USD',
      'CreditCard' => new CreditCard(array(
        'firstName' => 'Ryan',
        'lastName' => 'Dao',
        'type' => 'MasterCard',
        'month' => '11',
        'year' => '2016',
        'number' => '4381258770269608'             
      ))
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

class PayPalDirectMockTest extends PayPalDirectTest {
  
  function setUp() {
    parent::setUp();
    
    Config::inst()->remove('PaymentGateway', 'environment');
    Config::inst()->update('PaymentGateway', 'environment', 'test');
    $this->processor = PaymentFactory::factory('PayPalDirect');
  }
  
  function testClassConfig() {
    $this->assertEquals(get_class($this->processor), 'PaymentProcessor_MerchantHosted');
    $this->assertEquals(get_class($this->processor->gateway), 'PayPalDirectGateway_Mock');
    $this->assertEquals(get_class($this->processor->payment), 'PayPal');
  }
  
  function testPaymentSuccess() {
    $response = $this->processor->gateway->process($this->data);
    $result = $this->processor->gateway->getResponse($response);
    $this->assertEquals($result->getStatus(), PaymentGateway_Result::SUCCESS);
  }
  
  function testPaymentFailure() {
    $this->data['Amount'] = '10.01';
    $response = $this->processor->gateway->process($this->data);
    $result = $this->processor->gateway->getResponse($response);
    $this->assertEquals($result->getStatus(), PaymentGateway_Result::FAILURE);
  }
}