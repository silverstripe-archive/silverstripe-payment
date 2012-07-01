<?php

class DummyMerchantHostedTest extends SapphireTest {
  
  /* Black-box testing */
  function testClassConfig() {
    $controller = Payment_Controller::factory('DummyMerchantHosted');
    $this->assertEquals(get_class($controller), 'Payment_Controller_MerchantHosted');
    $this->assertEquals(get_class($controller->gateway), 'DummyMerchantHosted_Gateway');
    $this->assertEquals(get_class($controller->payment), 'Payment');
  }
  
  function testProcessPayment() {
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
    
    $controller = Payment_Controller::factory('DummyMerchantHosted');
    $successResult = $controller->processPayment($successData);
    $failureResult = $controller->processPayment($failureData);
    $incompleteResult = $controller->processPayment($incompleteData);
    
    $this->assertEquals($successResult, new Payment_Gateway_Result(Payment_Gateway_Result::SUCCESS));
    $this->assertEquals($failureResult, new Payment_Gateway_Result(Payment_Gateway_Result::FAILURE));
    $this->assertEquals($incompleteResult, new Payment_Gateway_Result(Payment_Gateway_Result::INCOMPLETE));
    
    // TODO: Assert correct model
  }  
}

class DummyGatewayHostedTest extends FunctionalTest {
  
  /* Black-box testing */
  function testClassConfig() {
    $controller = Payment_Controller::factory('DummyGatewayHosted');
    $this->assertEquals(get_class($controller), 'Payment_Controller_GatewayHosted');
    $this->assertEquals(get_class($controller->gateway), 'DummyGatewayHosted_Gateway');
    $this->assertEquals(get_class($controller->payment), 'Payment');
  }
  
  function testPaymentFlow() {
    
  }
}