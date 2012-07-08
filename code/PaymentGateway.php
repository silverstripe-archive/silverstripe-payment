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
   * Get the gateway type set by the yaml config ('live', 'dev', 'mock')
   */
  public static function get_environment() {
    return Config::inst()->get('PaymentGateway', 'environment');
  }

  public function setReturnURL($url) {
    $this->returnURL = $url;
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
class PaymentGateway_Result {

  const SUCCESS = 'Success';
  const FAILURE = 'Failure';
  const INCOMPLETE = 'Incomplete';
  
  /**
   * Status of payment processing
   * 
   * @var String
   */
  protected $status;
  
  /**
   * Message passed back from external gateways
   * 
   * @var String
   */
  protected $message;

  function __construct($status = null, $message = null) {
    if ($status == self::SUCCESS || $status == self::FAILURE || $status == self::INCOMPLETE) {
      $this->status = $status;
      $this->message = $message;
    } else {
      user_error("Invalid result status", E_USER_ERROR);
    }
  }

  function getStatus() {
    return $this->status;
  }
  
  function getMessage() {
    return $this->message;
  }
}