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
  protected static $type;
  
  public static function set_type($type) {
    if ($type != 'dev' && $type != 'live' && $type != 'test') {
      user_error("Gateway type undefined", E_USER_ERROR);
    } else {
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
      user_error("Gateway class is not defined", E_USER_ERROR);
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
}
