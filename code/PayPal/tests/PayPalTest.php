<?php

class PayPalTest extends SapphireTest {
  
  function setUp() {
    parent::setUp();
  
    $paymentMethods = array(
        'PayPalDirect',
        'PayPalExpress'
    );
    Config::inst()->remove('PaymentProcessor', 'supported_methods');
    Config::inst()->update('PaymentProcessor', 'supported_methods', $paymentMethods);
  
    $supportedMethods = Config::inst()->get('PaymentProcessor', 'supported_methods');
    $this->assertEquals($supportedMethods, $paymentMethods);
  }
  
  function testPayPalURL() {
    $configDev = Config::inst()->get('PayPalGateway', 'dev');
    $this->assertEquals($configDev['url'], 'https://api-3t.sandbox.paypal.com/nvp');
    
    $configLive = Config::inst()->get('PayPalGateway', 'live');
    $this->assertEquals($configDev['url'], 'https://api-3t.paypal.com/nvp');
  }
}

class PayPalDirectTest extends PayPalTest {
  
  function setUp() {
    parent::setUp();
  }
  
  function testClassConfig() {
    $controller = PaymentFactory::factory('PayPalDirect');
    $this->assertEquals(get_class($controller), 'PaymentProcessor_MerchantHosted');
    $this->assertEquals(get_class($controller->gateway), 'PayPalDirectGateway');
    $this->assertEquals(get_class($controller->payment), 'PayPal');
  }
  
  function testPaymentSuccess() {
    $controller = PaymentFactory::factory('PayPalDirect');
    $data = array(
      'Amount' => '10.00',
      'Currency' => 'USD',
      'CreditCardType' => 'Visa',
      'CardNumber' => '4381258770269608',
      'DateExpiry'=> '112020',
      'Cvv2' => '000',
      'FirstName' => 'Ryan',
      'LastName' => 'Dao'
    );
    
    $result = $controller->gateway->getResponse($controller->gateway->process($data));
    $this->assertEquals($result->getStatus(), PaymentGateway_Result::SUCCESS);
  }
  
  function testPaymentFailure() {
    $controller = PaymentFactory::factory('PayPalDirect');
    $data = array(
      'Amount' => '10.00',
      'Currency' => '123',
      'CreditCardType' => 'Visa',
      'CardNumber' => '4381258770269608',
      'DateExpiry'=> '112020',
      'Cvv2' => '000',
      'FirstName' => 'Ryan',
      'LastName' => 'Dao'
    );
    
    $result = $controller->gateway->getResponse($controller->gateway->process($data));
    $this->assertEquals($result->getStatus(), PaymentGateway_Result::FAILURE);
  }
}

class PayPalExpressTest extends PayPalTest {
  
  function setUp() {
    parent::setUp();
  }
  
  function testClassConfig() {
    $controller = PaymentFactory::factory('PayPalExpress');
    $this->assertEquals(get_class($controller), 'PaymentProcessor_GatewayHosted');
    $this->assertEquals(get_class($controller->gateway), 'PayPalExpressGateway');
    $this->assertEquals(get_class($controller->payment), 'PayPal');
  }
  
  function testSetExpressCheckout() {
    $controller = PaymentFactory::factory('PayPalExpress');
    $controller->gateway->setReturnURL('www.example.com');
    $controller->gateway->setCancelURL('www.example.com');
    
    $token = $controller->gateway->setExpressCheckout();
    $this->assertNotNull($token);
  }
}