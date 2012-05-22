<?php 

/**
 * "Abstract" class for a number of payment models
 * 
 *  @package payment
 */
class Payment extends DataObject {
  /**
   * Construct the underlying payment object given the payment gateway module name
   */
  public function __construct($gatewayName) {
    
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
  
      //Usered for store any Exception during this payment Process.
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

?>