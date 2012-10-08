<?php

/**
 * Model class for a number of payment models
 */
class Payment extends DataObject {

  /* Constants for payment statuses */
  const SUCCESS = 'Success';
  const FAILURE = 'Failure';
  const INCOMPLETE = 'Incomplete';
  const PENDING = 'Pending';

  /**
   * Status:
   *   - Incomplete (default): Payment created but nothing confirmed as successful
   *   - Success: Payment successful
   *   - Failure: Payment failed during process
   *   - Pending: Payment awaiting receipt/bank transfer etc
   *   - Incomplete: Payment cancelled
   * Amount: The payment amount amd currency
   * ErrorMessage: Error(s) returned from the gateway
   * HTTPStatus: Status code of the HTTP response
   * Method: The payment method used
   */
  public static $db = array(
    'Status' => "Enum('Incomplete, Success, Failure, Pending')",
    'Amount' => 'Money',
    'ErrorMessage' => 'Text',
    'ErrorCode' => 'Varchar(10)',
    'HTTPStatus' => 'Varchar(10)',
    'Method' => 'Varchar(100)'
  );

  public static $has_one = array(
    'PaidBy' => 'Member',
  );

  /**
   * Make payment table transactional.
   */
  static $create_table_options = array(
    'MySQLDatabase' => 'ENGINE=InnoDB'
  );

  /**
   * Update the status of this payment
   *
   * @param String $status
   * @param String $HTTPStatus
   * @param String $message
   * @param array|String $error
   * @return bool true if successful, false otherwise
   */
  public function updateStatus($status, $HTTPStatus = null, $error = null) {
    if ($status == self::SUCCESS || $status == self::FAILURE ||
        $status == self::INCOMPLETE || $status == self::PENDING) {

      // Save HTTP status
      if (! $HTTPStatus) {
        $HTTPStatus = '200';
      }
      $this->HTTPStatus = $HTTPStatus;

      // Save error messages and codes
      if ($error) {
        if (! is_array($error)) {
          throw new Exception("Error parameter is not an array");
        } else {
          $this->ErrorCode = implode(';', array_keys($error));
          $this->ErrorMessage = implode(';', array_values($error));
        }
      }

      // Save payment status and write to database
      $this->Status = $status;
      $this->write();

      return true;
    } 
    else {
      throw new Exception("Payment status invalid");
    }
  }
}