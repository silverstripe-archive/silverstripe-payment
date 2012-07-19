<?php

class CreditCardTest extends SapphireTest {
  
  public $card;
  
  function setUp() {
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
  
  function testIsExpired() {
    $previousMonth = date('n', strtotime('-1 Month', time()));
    
    $this->card->month = $previousMonth;
    $this->assertTrue($this->card->isExpired());
  } 
  
  function testIsNotExpired() {
    $nextMonth = date('n', strtotime('+1 Month', time()));
    
    $this->card->month = $nextMonth;
    $this->assertFalse($this->card->isExpired());
  }
  
  function testValidateEssentialAttributes() {
    $this->card->validateEssentialAttributes();
    $this->assertTrue($this->card->validationResult->valid());
  }

  function testFirstNameNull() {
    $this->card->firstName = '';
    $this->card->validateEssentialAttributes();
    $this->assertFalse($this->card->validationResult->valid());
    $this->assertEquals($this->card->validationResult->message(), "First name cannot be empty");
  }
  
  function testLastNameNull() {
    $this->card->lastName = '';
    $this->card->validateEssentialAttributes();
    $this->assertFalse($this->card->validationResult->valid());
    $this->assertEquals($this->card->validationResult->message(), "Last name cannot be empty");
  }
  
  function testInvalidExpiryDate() {
    $this->card->month = '13';
    $this->card->validateEssentialAttributes();
    $this->assertFalse($this->card->validationResult->valid());
    $this->assertEquals($this->card->validationResult->message(), "Expiration date not valid");
  }
  
  function testValidCardType() {
    $this->card->validateCardType();
    $this->assertTrue($this->card->validationResult->valid());
  }
  
  function testInvalidCardType() {
    $this->card->type = 'random';
    $this->card->validateCardType();
    $this->assertFalse($this->card->validationResult->valid());
    $this->assertEquals($this->card->validationResult->message(), "Credit card type is invalid");
  }
  
  function testCardNumber() {
    $this->card->validateCardNumber();
    $this->assertTrue($this->card->validationResult->valid());
  }
}