<?php

class DummyMerchantHostedTest extends SapphireTest {
  
  /* Black-box testing */
  function testClassConfig() {
    $controller = PaymentProcessor::factory('DummyMerchantHosted');
    $this->assertEquals(get_class($controller), 'PaymentProcessor_MerchantHosted');
    $this->assertEquals(get_class($controller->gateway), 'DummyMerchantHostedGateway');
    $this->assertEquals(get_class($controller->payment), 'Payment');
  }
  
  function testprocessRequest() {
    $successData = array(
      'Amount' => '10',
      'Currency' => 'USD'   
    );
    
    $failureData = array(
      'Amount' => '10.01',
      'Currency' => 'USD'  
    );
    
    $incompleteData = array(
      'Amount' => '10.02',
      'Currency' => 'USD'  
    );
    
    $controller = PaymentProcessor::factory('DummyMerchantHosted');
    
    $controller->processRequest($successData);
    $this->assertEquals($controller->payment->Status, Payment::SUCCESS);
    
    $failureResult = $controller->processRequest($failureData);
    $this->assertEquals($controller->payment->Status, Payment::FAILURE);
    
    $incompleteResult = $controller->processRequest($incompleteData);
    $this->assertEquals($controller->payment->Status, Payment::INCOMPLETE);
  }  
}

class DummyGatewayHostedTest extends FunctionalTest {
  
  /* Black-box testing */
  function testClassConfig() {
    $controller = PaymentProcessor::factory('DummyGatewayHosted');
    $this->assertEquals(get_class($controller), 'PaymentProcessor_GatewayHosted');
    $this->assertEquals(get_class($controller->gateway), 'DummyGatewayHostedGateway');
    $this->assertEquals(get_class($controller->payment), 'Payment');
  }
  
  function testPaymentFlow() {
    
  }
}