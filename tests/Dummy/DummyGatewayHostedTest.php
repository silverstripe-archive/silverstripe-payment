<?php

class DummyGatewayHostedTest extends SapphireTest {

  public $data;
  public $processor;
  public $returnURL;

  function setUp() {
    parent::setUp();

    $paymentMethods = array('dev' => array('DummyGatewayHosted'));
    Config::inst()->remove('PaymentProcessor', 'supported_methods');
    Config::inst()->update('PaymentProcessor', 'supported_methods', $paymentMethods);

    Config::inst()->remove('PaymentGateway', 'environment');
    Config::inst()->update('PaymentGateway', 'environment', 'test');

    $this->data = array(
      'Amount' => '10',
      'Currency' => 'USD',
    );

    $this->returnURL = '/DummyProcessor_GatewayHosted/complete/DummyGatewayHosted';

    $this->processor = PaymentFactory::factory('DummyGatewayHosted');
  }

  function testClassConfig() {
    $controller = PaymentFactory::factory('DummyGatewayHosted');
    $this->assertEquals(get_class($controller), 'DummyProcessor_GatewayHosted');
    $this->assertEquals(get_class($controller->gateway), 'DummyGateway_GatewayHosted');
    $this->assertEquals(get_class($controller->payment), 'Payment');
  }

  function testConnectionError() {
    $this->data['Amount'] = 0.01;
    $result = $this->processor->capture($this->data);
    $this->assertEquals($this->processor->payment->Status, Payment::FAILURE);
    $this->assertEquals($this->processor->payment->HTTPStatus, '500');
  }

  function testPaymentSuccess() {
    $this->processor->capture($this->data);
    $paymentID = $this->processor->payment->ID;
    $this->returnURL .= "/$paymentID";

    $query = http_build_query(array('Status' => 'Success'));

    Director::test("DummyProcessor_GatewayHosted/complete/DummyGatewayHosted/$paymentID?$query");
    $payment = $payment = Payment::get()->byID($paymentID);
    $this->assertEquals($payment->Status, Payment::SUCCESS);
  }

  function testPaymentFailure() {
    $this->processor->capture($this->data);
    $paymentID = $this->processor->payment->ID;
    $this->returnURL .= "/$paymentID";

    $query = http_build_query(array(
      'Status' => 'Failure',
      'Message' => 'Payment cannot be completed',
      'ErrorMessage' => 'Internal Server Error',
      'ErrorCode' => '101'
    ));

    Director::test("DummyProcessor_GatewayHosted/complete/DummyGatewayHosted/$paymentID?$query");
    $payment = $payment = Payment::get()->byID($paymentID);
    $this->assertEquals($payment->Status, Payment::FAILURE);
    $this->assertEquals($payment->Message, 'Payment cannot be completed');
    $this->assertEquals($payment->ErrorMessage, 'Internal Server Error');
    $this->assertEquals($payment->ErrorCode, '101');
  }

  function testPaymentIncomplete() {
    $this->processor->capture($this->data);
    $paymentID = $this->processor->payment->ID;
    $this->returnURL .= "/$paymentID";

    $query = http_build_query(array(
      'Status' => 'Incomplete',
      'Message' => 'Awaiting payment confirmation'
    ));

    Director::test("DummyProcessor_GatewayHosted/complete/DummyGatewayHosted/$paymentID?$query");
    $payment = $payment = Payment::get()->byID($paymentID);
    $this->assertEquals($payment->Status, Payment::INCOMPLETE);
    $this->assertEquals($payment->Message, 'Awaiting payment confirmation');
  }
}