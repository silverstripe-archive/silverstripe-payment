<?php 

/**
 * "Abstract" class for a number of payment models
 * 
 *  @package payment
 */
class Payment extends DataObject {
  /**
   * Get the payment gateway class name from the associating module name
   * 
   * @param $gatewayName
   * @return gateway class name
   * 
   * TODO: Generalize naming convention
   *       Take care of merchant hosted and gateway hosted sub classes
   */
  public static function gatewayClassName($gatewayname) {
    $className = $gatewayname . "_Payment";
    if (class_exists($className)) {
      return $className;
    } else {
      return null;
    }
  }
  
  /**
   * Incomplete (default): Payment created but nothing confirmed as successful
   * Success: Payment successful
   * Failure: Payment failed during process
   * Pending: Payment awaiting receipt/bank transfer etc
   */
  public static $db = array(
      'Status' => "Enum('Incomplete,Success,Failure,Pending','Incomplete')",
      'Amount' => 'Money',
      'Message' => 'Text',
      'IP' => 'Varchar',
      'ProxyIP' => 'Varchar',
      'PaidForID' => "Int",
      'PaidForClass' => 'Varchar',
  
      //This is used only when the payment is one of the recurring payments, when a scheduler is trying to
      //find which is the latest one for the recurring payments
      'PaymentDate' => "Date",
  
      //Used for storing any Exception during this payment Process.
      'ExceptionError' => 'Text'
  );
  
  public static $has_one = array(
      'PaidObject' => 'Object',
      'PaidBy' => 'Member',
  );
}

class Payment_MerchantHosted extends Payment {
  
}

class Payment_GatewayHosted extends Payment {
  
}

/* Payment result classes */ 

abstract class Payment_Result {

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
class Payment_Success extends Payment_Result {

  function isSuccess() {
    return true;
  }

  function isProcessing() {
    return false;
  }

}
class Payment_Processing extends Payment_Result {

  function isSuccess() {
    return false;
  }

  function isProcessing() {
    return true;
  }

}
class Payment_Failure extends Payment_Result {

  function isSuccess() {
    return false;
  }

  function isProcessing() {
    return false;
  }
}

?>