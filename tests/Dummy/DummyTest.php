<?php

class DummyMerchantHostedTest extends SapphireTest {
  
  /* Black-box testing */
  function testClassConfig() {
    $controller = Payment_Controller::factory('DummyMerchantHosted');
    $this->assertEquals(get_class($controller), 'Payment_Controller_MerchantHosted');
    $this->assertEquals(get_class($controller->gateway), 'DummyMerchantHosted_Gateway');
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
    
    $controller = Payment_Controller::factory('DummyMerchantHosted');
    
    $controller->processRequest(null, $successData);
    $this->assertEquals($controller->payment->Status, Payment::SUCCESS);
    
    $failureResult = $controller->processRequest(null, $failureData);
    $this->assertEquals($controller->payment->Status, Payment::FAILURE);
    
    $incompleteResult = $controller->processRequest(null, $incompleteData);
    $this->assertEquals($controller->payment->Status, Payment::INCOMPLETE);
  }  
  
  function testMalformedPaymentData() {
    $data1 = array(
      'Amount' => '10'  
    );
    
    $data2 = array(
      'Currency' => 'USD'  
    );
    
    $data3 = array();
    
    $controller = Payment_Controller::factory('DummyMerchantHosted');
    $result1 = $controller->processRequest(null, $data1);
    $result2 = $controller->processRequest(null, $data2);
    $result3 = $controller->processRequest(null, $data3);
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