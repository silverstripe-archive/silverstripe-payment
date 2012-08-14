<?php

/**
 * "Abstract" class for a number of payment models
 *
 *  @package payment
 */
class Payment extends DataObject {

  /**
   * Constants for payment statuses
   * TODO maybe these constants should be linked/coupled to the Gateway constants?
   */
  const SUCCESS = 'Success';
  const FAILURE = 'Failure';
  const INCOMPLETE = 'Incomplete';
  const PENDING = 'Pending';

  public function getFormRequirements() {
    if (!$this->requiredFormFields) {
      $this->requiredFormFields = array();
    }

    return $this->requiredFormFields;
  }

  /**
   * Incomplete (default): Payment created but nothing confirmed as successful
   * Success: Payment successful
   * Failure: Payment failed during process
   * Pending: Payment awaiting receipt/bank transfer etc
   * Incomplete: Payment cancelled
   */
  public static $db = array(
    'Status' => "Enum('Incomplete, Success, Failure, Pending')",
    'Amount' => 'Money',
    'Message' => 'Text',
    'ErrorMessage' => 'Text',
    'ErrorCode' => 'Text',
    'HTTPStatus' => 'Text',
    'Method' => 'Text'
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
   * @param String HTTPStatus
   * @param String $message
   * @param array/String $error
   * @return true if successful, false otherwise
   */
  public function updateStatus($status, $HTTPStatus = null, $message = null, $error = null) {
    if ($status == self::SUCCESS || $status == self::FAILURE ||
        $status == self::INCOMPLETE || $status == self::PENDING) {

      // Save HTTP status
      if (! $HTTPStatus) {
        $HTTPStatus = '200';
      }
      $this->HTTPStatus = $HTTPStatus;

      // Save gateway message
      if ($message) {
        $this->Message = $message;
      }

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
    } else {
      throw new Exception("Payment status invalid");
    }
  }
}