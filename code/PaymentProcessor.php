<?php

/**
 * Default class for a number of payment controllers/processors.
 * This acts as a generic controller for all payment methods.
 * Override this class if desired to add custom functionalities.
 */
class PaymentProcessor extends Controller {
	
	/**
	 * The method name of this controller
	 *
	 * @var String
	 */
	protected $methodName;

	/**
	 * The payment object to be injected to this controller
	 *
	 * @var Payment
	 */
	public $payment;

	/**
	 * The gateway object to be injected to this controller
	 *
	 * @var PaymentGateway
	 */
	public $gateway;

	/**
	 * The payment data array
	 *
	 * @var array
	 */
	public $paymentData;

	/**
	 * Get the supported methods array set by the yaml configuraion
	 *
	 * @return array
	 */
	public static function get_supported_methods() {
		$methodConfig = Config::inst()->get('PaymentProcessor', 'supported_methods');
		$environment = PaymentGateway::get_environment();

		// Check if all methods are defined in factory
		foreach ($methodConfig[$environment] as $method) {
			if (! PaymentFactory::get_factory_config($method)) {
				user_error("Method $method not defined in factory", E_USER_ERROR);
			}
		}
		return $methodConfig[$environment];
	}

	/**
	 * Set the method name of this controller.
	 * This must be called after initializing a controller instance.
	 * If not a generic method name 'Payment' will be used.
	 *
	 * @param String $method
	 */
	public function setMethodName($method) {
		$this->methodName = $method;
	}

	/**
	 * Set the url to be redirected to after the payment is completed.
	 *
	 * @param String $url
	 */
	public function setRedirectURL($url) {
		Session::set('PostRedirectionURL', $url);
	}

	/**
	 * Get the url to be redirected to after the payment is completed.
	 *
	 * @return String
	 */
	public function getRedirectURL() {
		return Session::get('PostRedirectionURL');
	}

	/**
	 * Redirection after payment processing
	 */
	public function doRedirect() {
		// Put the payment ID in a session
		Session::set('PaymentID', $this->payment->ID);
		$this->extend('onBeforeRedirect');
		Controller::curr()->redirect($this->getRedirectURL());
	}

	/**
	 * Save preliminary data to database before processing payment
	 */
	public function setup() {
		$this->payment->Amount->Amount = $this->paymentData['Amount'];
		$this->payment->Amount->Currency = $this->paymentData['Currency'];
		$this->payment->Reference = isset($this->paymentData['Reference']) ? $this->paymentData['Reference'] : null;
		$this->payment->Status = Payment::PENDING;
		$this->payment->Method = $this->methodName;
		$this->payment->write();
	}

	/**
	 * Process a payment request. To be extended by individual processor type
	 * If there's no break point (i.e exceptions and errors), this should
	 * redirect to the postRedirectURL (merchant-hosted) or the external gateway (gateway-hosted)
	 * 
	 * Data passed in the format (Reference is optional)
	 * array('Amount' => 1.00, 'Currency' => 'USD', 'Reference' => 'Ref')
	 *
	 * @see paymentGateway::validate()
	 * @param Array $data Payment data
	 */
	public function capture($data) {
		$this->paymentData = $data;

		// Do pre-processing
		$this->setup();

		// Validate the payment data
		$validation = $this->gateway->validate($this->paymentData);
		if (! $validation->valid()) {
			// Use the exception message to identify this is a validation exception
			// Payment pages can call gateway->getValidationResult() to get all the
			// validation error messages
			throw new Exception("Validation Exception");
		}
	}

	/**
	 * Get the processor's form fields. Custom controllers use this function
	 * to add the form fields specifically to gateways.
	 *
	 * @return FieldList
	 */
	public function getFormFields() {
		$fieldList = new FieldList();

		$fieldList->push(new NumericField('Amount', 'Amount', ''));
		$fieldList->push(new DropDownField('Currency', 'Select currency :', $this->gateway->getSupportedCurrencies()));

		return $fieldList;
	}

	/**
	 * Get the form requirements
	 *
	 * @return RequiredFields
	 */
	public function getFormRequirements() {
		return new RequiredFields('Amount', 'Currency');
	}
}

/**
 * Default class for merchant-hosted controllers
 */
class PaymentProcessor_MerchantHosted extends PaymentProcessor {

	/**
	 * Process a merchant-hosted payment. Users will remain on the site
	 * until the payment is completed. Redirect to the postRedirectURL afterwards
	 *
	 * @see PaymentProcessor::capture()
	 * @param Array $data
	 */
	public function capture($data) {
		parent::capture($data);

		$result = $this->gateway->process($this->paymentData);
		$this->payment->updateStatus($result);

		// Do redirection
		$this->doRedirect();
	}

	/**
	 * Return the form fields for credit data
	 * 
	 * @return FieldList
	 */
	public function getCreditCardFields() {

		$months = array_combine(range(1, 12), range(1, 12));
		$years = array_combine(range(date('Y'), date('Y') + 10), range(date('Y'), date('Y') + 10));

		$fieldList = new FieldList();
		$fieldList->push(new DropDownField('CreditCardType', 'Select Credit Card Type :', $this->gateway->getSupportedCardTypes()));
		$fieldList->push(new TextField('FirstName', 'First Name:'));
		$fieldList->push(new TextField('LastName', 'Last Name:'));
		$fieldList->push(new CreditCardField('CardNumber', 'Credit Card Number:'));
		$fieldList->push(new DropDownField('MonthExpiry', 'Expiration Month: ', $months));
		$fieldList->push(new DropDownField('YearExpiry', 'Expiration Year: ', $years));
		$fieldList->push(new TextField('Cvc2', 'Credit Card CVN: (3 or 4 digits)', '', 4));

		return $fieldList;
	}

	/**
	 * Override to add credit card form fields
	 * 
	 * @return FieldList
	 */
	public function getFormFields() {
		$fieldList = parent::getFormFields();
		$fieldList->merge($this->getCreditCardFields());

		return $fieldList;
	}

	/**
	 * Override to add credit card form requirements
	 *
	 * @return RequiredFields
	 */
	public function getFormRequirements() {
		$required = parent::getFormRequirements();
		$required->appendRequiredFields(new RequiredFields(
			'FirstName',
			'LastName',
			'CardNumber',
			'MonthExpiry',
			'YearExpiry',
			'Cvc2'
		));
		return $required;
	}
}

/**
 * Default class for gateway-hosted processors
 */
class PaymentProcessor_GatewayHosted extends PaymentProcessor {

	private static $allowed_actions = array(
		'complete',
		'cancel'
	);

	/**
	 * Process a gateway-hosted payment. Users will be redirected to
	 * the external gateway to enter payment info. Redirect back to
	 * our site when the payment is completed.
	 *
	 * @see PaymentProcessor::capture()
	 * @param Array $data
	 */
	public function capture($data) {
		parent::capture($data);

		// Set the return link
		$this->gateway->returnURL = Director::absoluteURL(Controller::join_links(
				$this->link(),
				'complete',
				$this->methodName,
				$this->payment->ID
		));
		
		// Set the cancel link
		$this->gateway->cancelURL = Director::absoluteURL(Controller::join_links(
				$this->link(),
				'cancel',
				$this->methodName,
				$this->payment->ID
		));

		// Send a request to the gateway
		$result = $this->gateway->process($this->paymentData);

		// Processing may not get to here if all goes smoothly, customer will be at the 3rd party gateway
		if ($result && !$result->isSuccess()) {

			// Gateway did not respond or responded with error
			// Need to save the gateway response and save HTTP Status, errors etc. to Payment
			$this->payment->updateStatus($result);

			// Payment has failed - redirect to confirmation page
			// Developers can get the failure data from the database to show
			// the proper errors to users
			$this->doRedirect();
		}
	}

	/**
	 * Process request from the external gateway, this action is usually triggered if the payment was completed on the gateway 
	 * and the user was redirected to the returnURL.
	 * 
	 * The request is passed to the gateway so that it can process the request and use a mechanism to check the status of the payment.
	 *
	 * @param SS_HTTPResponse $request
	 */
	public function complete($request) {
		// Reconstruct the payment object
		$this->payment = Payment::get()->byID($request->param('OtherID'));

		// Reconstruct the gateway object
		$methodName = $request->param('ID');
		$this->gateway = PaymentFactory::get_gateway($methodName);

		// Query the gateway for the payment result
		$result = $this->gateway->check($request);
		$this->payment->updateStatus($result);

		// Do redirection
		$this->doRedirect();
	}
	
	/**
	 * Process request from the external gateway, this action is usually triggered if the payment was cancelled
	 * and the user was redirected to the cancelURL.
	 * 
	 * @param SS_HTTPResponse $request
	 */
	public function cancel($request) {
		// Reconstruct the payment object
		$this->payment = Payment::get()->byID($request->param('OtherID'));

		// Reconstruct the gateway object
		$methodName = $request->param('ID');
		$this->gateway = PaymentFactory::get_gateway($methodName);

		// The payment result was a failure
		$this->payment->updateStatus(new PaymentGateway_Failure());

		// Do redirection
		$this->doRedirect();
	}
}