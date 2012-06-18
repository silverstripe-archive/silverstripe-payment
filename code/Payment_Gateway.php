<?php

/**
 * Abstract class for a number of payment gateways 
 * 
 * @package payment
 */
class Payment_Gateway {
  
  /**
   * Type of the gateway to be used: dev/live/test
   */
  protected static $type = 'live';
  
  /**
   * The gateway class in use
   */
  protected  static $gateway_class;
  
  public static function set_type($type) {
    if ($type == 'dev' || $type == 'live' || $type == 'test') {
      self::$type = $type;
    }
  }
  
  /**
   * Get the class name of the desired gateway.
   * TODO: Find a better and cleaner way than forcing naming convention
   * 
   * @param unknown_type $gatewayName
   */
  public static function gateway_class_name($gatewayName) {
    if (! self::$type) {
      return null;
    }
    
    switch(self::$type) {
      case 'live':
        $gatewayClass = $gatewayName . '_Production_Gateway';
        break;
      case 'dev':
        $gatewayClass = $gatewayName . '_Sandbox_Gateway';
        break;
      case 'test':
        $gatewayClass = $gatewayName . '_Mock_Gateway';
        break;
    }
    
    if (class_exists($gatewayClass)) {
      return $gatewayClass;
    } else {
      return null;
    }
  }
  
  /**
   * Send a request to the gateway to process the payment. 
   * To be implemented by individual gateway
   * 
   * @param $data
   */
  public function process($data) {
    user_error("Please implement process() on $this->class", E_USER_ERROR);
  } 
  
  public function getResponse($data) {
    
  }
}

/**
 * Abstract class for gateway results
 */
abstract class Gateway_Result {

  protected $value;

  function __construct($value = null) {
    $this->value = $value;
  }

  function getValue() {
    return $this->value;
  }

  abstract function isSuccess();

  abstract function isProcessing();
}

class Gateway_Success extends Gateway_Result {

  function isSuccess() {
    return true;
  }

  function isProcessing() {
    return false;
  }
}

class Gateway_Processing extends Gateway_Result {

  function isSuccess() {
    return false;
  }

  function isProcessing() {
    return true;
  }
}

class Gateway_Failure extends Gateway_Result {

  function isSuccess() {
    return false;
  }

  function isProcessing() {
    return false;
  }
}