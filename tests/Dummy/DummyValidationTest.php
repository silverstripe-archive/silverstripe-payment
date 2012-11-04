<?php

class DummyValidationTest extends SapphireTest {

	public $processor;

	public function setUp() {
		parent::setUp();

		$paymentMethods = array('dev' => array('DummyMerchantHosted'));
		Config::inst()->remove('PaymentProcessor', 'supported_methods');
		Config::inst()->update('PaymentProcessor', 'supported_methods', $paymentMethods);

		Config::inst()->remove('PaymentGateway', 'environment');
		Config::inst()->update('PaymentGateway', 'environment', 'dev');

		$this->processor = PaymentFactory::factory('DummyMerchantHosted');
	}

	public function testAmoutNotSet() {
		$data = array('Currency' => 'USD');
		$result = $this->processor->gateway->validate($data);
		$this->assertEquals($result->message(), 'Payment amount not set');
	}

	public function testAmountNull() {
		$data = array('Amount' => '', 'Currency' => 'USD');
		$result = $this->processor->gateway->validate($data);
		$this->assertEquals($result->message(), 'Payment amount cannot be null');
	}

	public function testCurrencyNotSet() {
		$data = array('Amount' => '10');
		$result = $this->processor->gateway->validate($data);
		$this->assertEquals($result->message(), 'Payment currency not set');
	}

	public function testCurrencyNull() {
		$data = array('Amount' => '10', 'Currency' => '');
		$result = $this->processor->gateway->validate($data);
		$this->assertEquals($result->message(), 'Payment currency cannot be null');
	}

	public function testCurrencyNotSupported() {
		$data = array('Amount' => '10', 'Currency' => 'AUD');
		$result = $this->processor->gateway->validate($data);
		$this->assertEquals($result->message(), 'Currency AUD not supported by this gateway');
	}

	public function testCustomValidation() {
		$data = array('Amount' => '0.11', 'Currency' => 'USD');
		$result = $this->processor->gateway->validate($data);
		$this->assertEquals($result->message(), 'Cents value is .11');
	}
}