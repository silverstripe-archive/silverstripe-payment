<?php

/**
 * Object encapsulating credit card details and validation
 */
class CreditCard {
  
  /**
   * The card number patterns for credit card companies
   * @var array
   */
  private static $CARD_COMPANIES = array(
    'visa' => '/^4\d{12}(\d{3})?$/',
    'master' => '/^(5[1-5]\d{4}|677189)\d{10}$/',
    'discover' => '/^(6011|65\d{2})\d{12}$/',
    'american_express' => '/^3[47]\d{13}$/',
    'diners' => '/^3(0[0-5]|[68]\d)\d{11}$/'
  );
  
  /**
   * A mapping of the card types and their display names
   * @var array
   */
  private static $CARD_TYPE_NAMES = array(
    'visa' => 'Visa',
    'master' => 'MasterCard',
    'american_express' => 'American Express',
    'discover' => 'Discover',
    'diners' => 'Diners Club'
  );
  
  /* Credit Card data */
  public $firstName;
  public $lastName;
  public $month;
  public $year;
  public $type;
  public $number;
  
  /**
   * A ValidationResult object that stores the validation status and errors
   * 
   * @var ValidationResult
   */ 
  private $validationResult;
  
  /**
   * Helper function to retrieve the display names for an array of 
   * credit card types.
   * 
   * @param array $creditCardTypes
   * @return array('CC type id' => 'CC type displays').
   */
  public static function getCreditCardTypeDisplays($creditCardTypes) {
    $displays = array();

    foreach ($creditCardTypes as $type) {
      $displays[$type] = self::$CARD_TYPE_NAMES[$type];
    }
    
    return $displays;
  }
  
  public function __construct($options) {
    $this->firstName = $options['firstName'];
    $this->lastName = $options['lastName'];
    $this->month = $options['month'];
    $this->year = $options['year'];
    $this->type = $options['type'];
    $this->number = $options['number'];
    $this->validationResult = new ValidationResult();
  }
  
  public function getValidationResult() {
    return $this->validationResult;
  }
  
  /**
   * Check if the card is already exprired
   */
  public function isExpired() {
    // last day of the expiration month
    $expriration = strtotime(date('Y-m-t', mktime(0, 0, 0, $this->month, 1, $this->year)));  
    
    return (time() >= $expriration);
  }
  
  public function validateEssentialAttributes() {
    if ($this->firstName === null || $this->firstName == "") {
      $this->validationResult->error('First name cannot be empty');
    }
    
    if ($this->lastName === null || $this->lastName == "") {
      $this->validationResult->error('Last name cannot be empty');
    }
    
    if (checkdate($this->month, 1, $this->year) === false) {
      $this->validationResult->error('Expiration date not valid');
    }
    
    if ($this->isExpired() === true) {
      $this->validationResult->error('Expired');
    }
  }
  
  public function validateCardType() {
    if ($this->type === null || $this->type == "") {
      $this->validationResult->error('Credit card type is required');
    }
    
    if (!isset(self::$CARD_COMPANIES[$this->type])) {
      $this->validationResult->error('Credit card type is invalid');
    }
  }
  
  public function validateCardNumber() {
    if (strlen($this->number) < 12) {
      $this->validationResult->error('Card number length is invalid');
    }
    
    // Luhn algorithm to check the validity of the card number
    $map = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 0, 2, 4, 6, 8, 1, 3, 5, 7, 9);
    $sum = 0;
    $last = strlen($this->number) - 1;
    for ($i = 0; $i <= $last; $i++) {
      $sum += $map[$this->number[$last - $i] + ($i & 1) * 10];
    }
    if (! ($sum % 10 == 0)) {
      $this->validationResult->error('Card number is invalid');
    }
  }
  
  public function validate() {
    $this->validateEssentialAttributes();
    $this->validateCardType();
    $this->validateCardNumber();
    
    return $this->validationResult;
  }
}