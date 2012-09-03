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
    'Method' => 'Varchar(100)',
    'Status' => "Enum('Incomplete, Success, Failure, Pending')",
    'Amount' => 'Money',
    'HTTPStatus' => 'Varchar(10)'
  );

  public static $has_one = array(
    'PaidBy' => 'Member',
  );

  public static $has_many = array(
    'Errors' => 'Payment_Error',
  );

  public function updateStatus(PaymentGateway_Result $result) {

    //Use the gateway result to update the payment
    $this->Status = $result->getStatus();
    $this->HTTPStatus = $result->getHTTPResponse()->getStatusCode();

    $errors = $result->getErrors();
    foreach ($errors as $code => $message) {
      $error = new Payment_Error();
      $error->ErrorCode = $code;
      $error->ErrorMessage = $message;
      $error->PaymentID = $this->ID;
      $error->write();
    }

    return $this->write();
  }
}

/**
 * Class to represent error returned from payment gateway
 */
class Payment_Error extends DataObject {

  public static $db = array(
    'ErrorCode' => 'Varchar(10)',
    'ErrorMessage' => 'Text'
  );

  public static $has_one = array(
    'Payment' => 'Payment',
  );
}