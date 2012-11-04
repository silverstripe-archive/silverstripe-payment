<?php

class CreditCardTest extends SapphireTest {

	public $card;

	public function setUp() {
		parent::setUp();

		$this->card = new CreditCard(array(
			'firstName' => 'Test',
			'lastName' => 'Test',
			'month' => '11',
			'year' => date('Y'),
			'type' => 'master',
			'number' => '4381258770269608'
		));
	}

	public function testIsExpired() {
		$previousMonth = date('n', strtotime('-1 Month', time()));

		$this->card->month = $previousMonth;
		$this->assertTrue($this->card->isExpired());
	}

	public function testIsNotExpired() {
		$nextMonth = date('n', strtotime('+1 Month', time()));

		$this->card->month = $nextMonth;
		$this->assertFalse($this->card->isExpired());
	}

	public function testValidateEssentialAttributes() {
		$this->card->validateEssentialAttributes();
		$this->assertTrue($this->card->getValidationResult()->valid());
	}

	public function testFirstNameNull() {
		$this->card->firstName = '';
		$this->card->validateEssentialAttributes();
		$this->assertFalse($this->card->getValidationResult()->valid());
		$this->assertEquals($this->card->getValidationResult()->message(), "First name cannot be empty");
	}

	public function testLastNameNull() {
		$this->card->lastName = '';
		$this->card->validateEssentialAttributes();
		$this->assertFalse($this->card->getValidationResult()->valid());
		$this->assertEquals($this->card->getValidationResult()->message(), "Last name cannot be empty");
	}

	public function testInvalidExpiryDate() {
		$this->card->month = '13';
		$this->card->validateEssentialAttributes();
		$this->assertFalse($this->card->getValidationResult()->valid());
		$this->assertEquals($this->card->getValidationResult()->message(), "Expiration date not valid");
	}

	public function testValidCardType() {
		$this->card->validateCardType();
		$this->assertTrue($this->card->getValidationResult()->valid());
	}

	public function testInvalidCardType() {
		$this->card->type = 'random';
		$this->card->validateCardType();
		$this->assertFalse($this->card->getValidationResult()->valid());
		$this->assertEquals($this->card->getValidationResult()->message(), "Credit card type is invalid");
	}

	public function testCardNumber() {
		$this->card->validateCardNumber();
		$this->assertTrue($this->card->getValidationResult()->valid());
	}
}