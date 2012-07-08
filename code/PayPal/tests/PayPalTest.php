<?php

class PayPalTest extends SapphireTest {
  
  function testPayPalURL() {
    $configDev = Config::inst()->get('PayPalGateway', 'dev');
    $this->assertEquals($configDev['url'], 'https://api-3t.sandbox.paypal.com/nvp');
    
    $configLive = Config::inst()->get('PayPalGateway', 'live');
    $this->assertEquals($configDev['url'], 'https://api-3t.paypal.com/nvp');
  }
}

class PayPalDirectTest extends SapphireTest {
  
  function testClassConfig() {
    $controller = PaymentProcessor::factory('PayPalDirect');
    $this->assertEquals(get_class($controller), 'PaymentProcessor_MerchantHosted');
    $this->assertEquals(get_class($controller->gateway), 'PayPalDirectGateway');
    $this->assertEquals(get_class($controller->payment), 'PayPal');
  }
  
  function testPaymentSuccess() {
    $controller = PaymentProcessor::factory('PayPalDirect');
    $data = array(
      'Amount' => '10.00',
      'Currenct' => 'USD'
    );
    
    $result = $controller->gateway->process($data);
    $this->assertEquals($result->getStatus(), PaymentGateway_Result::SUCCESS);
  }
  
  function testPaymentFailure() {
    
  }
}