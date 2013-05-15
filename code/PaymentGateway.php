<?php
/**
 * Parent class for a number of payment gateways
 */
class PaymentGateway {
	
	/**
	 * The gateway url
	 * TODO: Can this just be moved to PaymentGateway_GatewayHosted?
	 *
	 * @var String
	 */
	public $gatewayURL;

	/**
	 * Object holding the gateway validation result
	 *
	 * @var ValidationResult
	 */
	private $validationResult;

	/**
	 * Object holding the result from gateway
	 *
	 * @var PaymentGateway_Result
	 */
	private $gatewayResult;

	/**
	 * Supported credit card types for this gateway
	 * 
	 * @see PaymentGateway::getSupportedCardTypes()
	 */
	protected $supportedCardTypes = array();

	/**
	 * Supported currencies for this gateway
	 * 
	 * @see PaymentGateway::getSupportedCurrencies()
	 */
	protected $supportedCurrencies = array();

	/**
	 * Array of config for this gateway
	 *
	 * @var Array
	 */
	protected $config;

	/**
	 * Get the payment environment.
	 * The environment is retrieved from the config yaml file.
	 * If no environment is specified, assume SilverStripe's environment.
	 */
	public static function get_environment() {
		if (Config::inst()->get('PaymentGateway', 'environment')) {
			return Config::inst()->get('PaymentGateway', 'environment');
		} else {
			return Director::get_environment_type();
		}
	}

	/**
	 * Get validation result for this gateway
	 * 
	 * @see PaymentGateway::validate()
	 * @return ValidationResult
	 */
	public function getValidationResult() {
		if (!$this->validationResult) {
			$this->validationResult = new ValidationResult();
		}
		return $this->validationResult;
	}

	/**
	 * Get the YAML config for current environment
	 * 
	 * @return Array
	 */
	public function getConfig() {
		if (!$this->config) {
			$this->config = Config::inst()->get(get_class($this), self::get_environment());
		}
		return $this->config;
	}

	/**
	 * Get the list of credit card types supported by this gateway
	 *
	 * @return Array Credit card types
	 */
	public function getSupportedCardTypes() {
		return $this->supportedCardTypes;
	}

	/**
	 * Get the list of currencies supported by this gateway
	 *
	 * @return Array Supported currencies
	 */
	public function getSupportedCurrencies() {
		return $this->supportedCurrencies;
	}

	/**
	 * Validate the payment data against the gateway-specific requirements
	 *
	 * @param Array $data
	 * @return ValidationResult
	 */
	public function validate($data) {

		$validationResult = $this->getValidationResult();

		if (! isset($data['Amount'])) {
			$validationResult->error('Payment amount not set');
		}
		else if (empty($data['Amount'])) {
			$validationResult->error('Payment amount cannot be null');
		}

		if (! isset($data['Currency'])) {
			$validationResult->error('Payment currency not set');
		}
		else if (empty($data['Currency'])) {
			$validationResult->error('Payment currency cannot be null');
		}
		else if (! array_key_exists($data['Currency'], $this->getSupportedCurrencies())) {
			$validationResult->error('Currency ' . $data['Currency'] . ' not supported by this gateway');
		}

		if (isset($data['CardNumber'])) {
			$options = array(
				'firstName' => $data['FirstName'],
				'lastName' => $data['LastName'],
				'month' => $data['MonthExpiry'],
				'year' => $data['YearExpiry'],
				'type' => $data['CreditCardType'],
			);
			if (is_array($data['CardNumber'])) {
				$options['number'] = implode('', $data['CardNumber']);
			} else {
				$options['number'] = $data['CardNumber'];
			}

			$cc = new CreditCard($options);
			$validationResult->combineAnd($cc->validate());
		}

		$this->validationResult = $validationResult;
		return $validationResult;
	}

	/**
	 * Send a request to the gateway to process the payment.
	 * To be implemented by individual gateways
	 *
	 * @param Array $data
	 * @return PaymentGateway_Result
	 */
	public function process($data) {
		return new PaymentGateway_Success();
	}
}

/**
 * Parent class for all merchant-hosted gateways
 */
class PaymentGateway_MerchantHosted extends PaymentGateway { 
}

/**
 * Parent class for all gateway-hosted gateways
 * 
 * TODO: Do the setters/getters for URLs really need to exist? Setters should either validate URL or be removed and 
 * instance vars should be public instead of protected - similar to gatewayURL?
 */
class PaymentGateway_GatewayHosted extends PaymentGateway {
	
	/**
	 * The link to return to after processing payment (for gateway-hosted payments only)
	 *
	 * @var String
	 */
	protected $returnURL;

	/**
	 * The link to return to after cancelling payment (for gateway-hosted payments only)
	 *
	 * @var String
	 */
	protected $cancelURL;

	/**
	 * Set the return url, default to the site root
	 *
	 * @param String $url
	 */
	public function setReturnURL($url = null) {
		if ($url) {
			$this->returnURL = $url;
		} else {
			$this->returnURL = Director::absoluteBaseURL();
		}
	}
	
	public function getReturnURL() {
		return $this->returnURL;
	}

	/**
	 * Set the cancel url, default to the site root
	 *
	 * @param String $url
	 */
	public function setCancelURL($url) {
		if ($url) {
			$this->cancelURL = $url;
		} else {
			$this->cancelURL = Director::absoluteBaseURL();
		}
	}

	public function getCancelURL() {
		return $this->cancelURL;
	}

	/**
	 * Pass the response object to the gateway and return the result
	 *
	 * @param SS_HTTPRequest $request
	 * @return PaymentGateway_Result
	 */
	public function getResponse($request) {
		return new PaymentGateway_Success();
	}
}

/**
 * Class for gateway results
 */
class PaymentGateway_Result {
	
	/* Constants for gateway result status */
	const SUCCESS = 'Success';
	const FAILURE = 'Failure';
	const INCOMPLETE = 'Incomplete';

	/**
	 * Status of the payment being processed
	 *
	 * @var String
	 */
	protected $status;

	/**
	 * Array of errors raised by the gateway
	 * array(ErrorCode => ErrorMessage)
	 *
	 * @var array
	 */
	protected $errors = array();

	/**
	 * The HTTP response object passed back from the gateway
	 *
	 * @var SS_HTTPResponse
	 */
	protected $HTTPResponse;

	/**
	 * @param String $status
	 * @param SS_HTTPResponse $response
	 * @param Array $errors
	 */
	public function __construct($status, $response = null, $errors = null) {

		if (!$response) {
			$response = new SS_HTTPResponse('', 200);
		}

		$this->HTTPResponse = $response;
		$this->setStatus($status);

		if ($errors) {
			$this->setErrors($errors);
		}
	}

	/**
	 * Set the payment result status.
	 *
	 * @param String $status
	 * @throws Exception when status is invalid
	 */
	public function setStatus($status) {
		if ($status == self::SUCCESS || $status == self::FAILURE || $status == self::INCOMPLETE) {
			$this->status = $status;
		} else {
			throw new Exception("Result status invalid");
		}
	}

	/**
	 * Get status of this result
	 * 
	 * @return String
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * Get HTTP Response
	 * 
	 * @return SS_HTTPResponse
	 */
	public function getHTTPResponse() {
		return $this->HTTPResponse;
	}

	/**
	 * Set the gateway errors
	 *
	 * @param array $errors
	 */
	public function setErrors($errors) {
		
		if (is_string($errors)) {
			$errors = array($errors);
		}
		
		if (is_array($errors)) {
			$this->errors = $errors;
		} else {
			throw new Exception("Gateway errors must be array");
		}
	}

	/**
	 * Add an error to the error list
	 *
	 * @param String $message: The error message
	 * @param String $code: The error code
	 */
	public function addError($message, $code = null) {
		if ($code) {
			if (array_key_exists($code, $this->errors)) {
				throw new Exception("Error code already exists");
			} else {
				$this->errors[$code] = $message;
			}
		} else {
			array_push($this->errors, $message);
		}
	}

	/**
	 * Get errors
	 * 
	 * @return Array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Returns true if successful
	 * 
	 * @return Boolean
	 */
	public function isSuccess() {
		return $this->status == self::SUCCESS;
	}

	/**
	 * Returns true if failure
	 * 
	 * @return Boolean
	 */
	public function isFailure() {
		return $this->status == self::FAILURE;
	}

	/**
	 * Returns true if incomplete
	 * 
	 * @return Boolean
	 */
	public function isIncomplete() {
		return $this->status == self::INCOMPLETE;
	}

}

/**
 * Wrapper class for 'success' result
 */
class PaymentGateway_Success extends PaymentGateway_Result {

	public function __construct() {
		parent::__construct(self::SUCCESS);
	}
}

/**
 * Wrapper class for 'failure' result
 */
class PaymentGateway_Failure extends PaymentGateway_Result {

	public function __construct($response = null, $errors = null) {
		parent::__construct(self::FAILURE, $response, $errors);
	}
}

/**
 * Wrapper class for 'incomplete' result
 */
class PaymentGateway_Incomplete extends PaymentGateway_Result {

	public function __construct($response = null, $errors = null) {
		parent::__construct(self::INCOMPLETE, $response, $errors);
	}
}
