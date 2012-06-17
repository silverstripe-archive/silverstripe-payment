<?php 

class Dummy_Payment_Gateway extends Payment_Gateway {
  protected static $test_mode = 'success';
  
  static function set_test_mode($test_mode) {
    if ($test_mode == 'success' || $test_mode == 'failure') {
      self::$test_mode = $test_mode;
    } else {
      user_error('Test mode not supported', E_USER_ERROR);
    }
  }
  
  public function process($data) {
    parent::process($data);
  }
}

/**
 * Dummy Payment Gateway class
 */
class Dummy_Production_Gateway extends Dummy_Payment_Gateway {
  
  public function process($data) {
    switch (self::$test_mode) {
      case 'success':
        return new Gateway_Success();
        break;
      case 'failure':
        return new Gateway_Failure();
        break;
      default:
        return new Gateway_Processing();
        break;
    }
  }
}