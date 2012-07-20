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