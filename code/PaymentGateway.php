<?php

/**
 * Abstract class for a number of payment gateways
 *
 * @package payment
 */
abstract class PaymentGateway {
  /**
   * The gateway url
   */
  public $gatewayURL;

  /**
   * The link to return to after processing payment
   */
  protected $returnURL;
  
  /**
   * The link to return to after cancelling payment
   */
  protected $cancelURL;
  
  /**
   * A ValidationResult object that holds the validation status
   * of the payment data.
   *
   * @var ValidationResult
   */
  public $validationResult;

  /**
   * Get the gateway type set by the yaml config ('live', 'dev', 'mock')
   */
  public static function get_environment() {
    return Config::inst()->get('PaymentGateway', 'environment');
  }

  public function setReturnURL($url = null) {
    if ($url) {
      $this->returnURL = $url;
    } else {
      $this->returnURL = Director::absoluteBaseURL();
    }
  }
  
  public function setCancelURL($url) {
    if ($url) {
      $this->cancelURL = $url;
    } else {
      $this->cancelURL = Director::absoluteBaseURL();
    }
  }
  
  /**
   * Get the list of credit card types supported by this gateway
   * 
   *  @return array('id' => 'Credit Card Type')
   */
  public function getSupportedCreditCardType() {
    return null;
  }

  /**
   * Validate the payment data against the gateway-specific requirements
   * 
   * @param array $data
   * @return ValidationResult
   */
  public function validatePaymentData($data) {
    if (! ($this->validationResult != null && $this->validationResult instanceof ValidationResult)) {
      $this->validationResult = new ValidationResult();
    }
    
    if (! isset($data['Amount'])) {
      $this->validationResult->error('Payment amount not set');
    }
    
    if (! $data['Amount']) {
      $this->validationResult->error('Payment amount cannot be null');
    } 
    
    if (! isset($data['Currency'])) {
      $this->validationResult->error('Payment currency not set');
    }
    
    if (! $data['Currency']) {
      $this->validationResult->error('Payment currency cannot be null');
    }
  }
  
  /**
   * Send a request to the gateway to process the payment.
   * To be implemented by individual gateway
   *
   * @param $data
   */
  abstract public function process($data);

  /**
   * Process the response from the external gateway
   *
   * @return PaymentGateway_Result
   */
  abstract public function getResponse($response);
  
  /**
   * Return a set of requirements for the payment data array for this gateway 
   */
  public function paymentDataRequirements() {
    return array('Amount', 'Currency');
  }

  /**
   * Post a set of payment data to a remote server
   *
   * @param array $data
   * @param String $endpoint. If not set, assume $gatewayURL
   *
   * @return RestfulService_Response
   * TODO: May consider subclasssing this to make it more suitable for our case
   */
  public function postPaymentData($data, $endpoint = null) {
    if (! $endpoint) {
      $endpoint = $this->gatewayURL;
    }

    $service = new RestfulService($endpoint);
    return $service->request(null, 'POST', $data);
  }
}

/**
 * Class for gateway results
 */
class PaymentGateway_Result extends ValidationResult {

  const SUCCESS = 'Success';
  const FAILURE = 'Failure';
  const INCOMPLETE = 'Incomplete';
  
  /**
   * Status of payment processing
   * 
   * @var String
   */
  protected $status;

  function __construct($valid = true, $message = null) {
    parent::__construct($valid, $message);
    
    if ($valid == true) {
      $this->status = self::SUCCESS;
    } else {
      $this->status = self::FAILURE;
    }
  }
  
  function setStatus($status) {
    if ($status == self::SUCCESS) {
      $this->valid = true;
      $this->status = $status;
    } else if ($status == self::FAILURE || $status == self::INCOMPLETE) {
      $this->valid = false;
      $this->status = $status;
    } else {
      user_error("Result status is invalid", E_USER_ERROR);
    }
  }
  
  function error($message, $code = null) {
    parent::error();
    
    $this->status = self::FAILURE;
  }

  public function getStatus() {
    return $this->status;
  }
}