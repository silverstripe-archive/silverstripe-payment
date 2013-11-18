<?php

/**
 * Model class for Payment
 */
class Payment extends DataObject {

	/* Constants for payment statuses */
	const SUCCESS = 'Success';
	const FAILURE = 'Failure';
	const INCOMPLETE = 'Incomplete';
	const PENDING = 'Pending';

	/**
	 * Method: The payment method used
	 * Status:
	 *   - Incomplete (default): Payment created but nothing confirmed as successful
	 *   - Success: Payment successful
	 *   - Failure: Payment failed during process
	 *   - Pending: Payment awaiting receipt/bank transfer etc
	 *   - Incomplete: Payment cancelled
	 * Amount: The payment amount amd currency
	 * HTTPStatus: Status code of the HTTP response
	 */
	private static $db = array(
		'Method' => 'Varchar(100)',
		'Status' => "Enum('Incomplete, Success, Failure, Pending')",
		'Amount' => 'Money',
		'HTTPStatus' => 'Varchar(10)',
		'Reference' => 'Varchar'
	);

	/**
	 * PaidBy: Member that processed this payment (optional)
	 */
	private static $has_one = array(
		'PaidBy' => 'Member',
	);

	/**
	 * Errors: Errors returned from payment gateway when processing this payment
	 */
	private static $has_many = array(
		'Errors' => 'Payment_Error',
	);

	/**
	 * Update the payment status inclusing saving any errors from the gateway
	 * 
	 * @see PaymentProcessor::capture()
	 * 
	 * @param PaymentGateway_Result Result from the payment gateway after processing
	 * @return Int Payment ID
	 */
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

	/**
	 * ErrorCode: Gateway specific error code
	 * ErrorMessage: Corresponding error message from the gateway
	 */
	private static $db = array(
		'ErrorCode' => 'Varchar(10)',
		'ErrorMessage' => 'Text'
	);

	/**
	 * Payment: Payment this error is related to
	 */
	private static $has_one = array(
		'Payment' => 'Payment',
	);
}