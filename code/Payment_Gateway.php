<?php

/**
 * Abstract class for a number of payment gateways 
 * 
 * @package payment
 */
class Payment_Gateway {
  
  /**
   * The link to return to after processing payment  
   */
  protected  $returnURL;
  
  /**
   * Type of the gateway to be used: dev/live/test
   * 
   * TODO: Replace this static variable with config option
   */
  protected static $type = 'live';
  
  public function setReturnURL($url) {
    $this->returnURL = $url;
  }
  
  public static function set_type($type) {
    if ($type == 'dev' || $type == 'live' || $type == 'test') {
      self::$type = $type;
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
  
  /**
   * Process the response from the external gateway
   * 
   * @return Gateway_Result
   */
  public function getResponse($response) {
    return new Gateway_Result(Gateway_Result::FAILURE);
  }
}

/**
 * Class for gateway results
 */
class Gateway_Result {
  
  const SUCCESS = 'Success';
  const FAILURE = 'Failure';
  const INCOMPLETE = 'Incomplete';
  
  protected $status;

  function __construct($status = null) {
    if ($status == self::SUCCESS || $status == self::FAILURE || $status == self::INCOMPLETE) {
      $this->status = $status;
    } else {
      user_error("Invalid result status", E_USER_ERROR);
    }
  }

  function getStatus() {
    return $this->status;
  }
}