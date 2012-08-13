<?php
/**
 * Default class for a number of payment controllers.
 * This acts as a generic controller for all payment methods.
 * Override this class if desired to add custom functionalities.
 *
 * Configuration format for PaymentProcessor:
 * PaymentProcessor:
 *   supported_methods:
 *     {environment}:
 *       - {method name}
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

  public function setRedirectURL($url) {
    Session::set('PostRedirectionURL', $url);
  }

  public function getRedirectURL() {
    return Session::get('PostRedirectionURL');
  }

  /**
   * Redirection for post-processing
   */
  public function doRedirect() {
    // Put the payment ID in a session
    Session::set('PaymentID', $this->payment->ID);
    Controller::curr()->redirect($this->getRedirectURL());
  }

  /**
   * Save preliminary data to database before processing payment
   */
  public function setup() {
    $this->payment->Amount->Amount = $this->paymentData['Amount'];
    $this->payment->Amount->Currency = $this->paymentData['Currency'];
    $this->payment->Status = Payment::PENDING;
    $this->payment->Method = $this->methodName;
    $this->payment->write();
  }

  /**
   * Process a payment request. To be extending by individual processor type
   *
   * @param $data
   * @return Payment
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
      // TODO: It's better to have a separate exception class for this.
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

    $currencies = array_combine($this->gateway->getSupportedCurrencies(), $this->gateway->getSupportedCurrencies());
    $fieldList->push(new DropDownField('Currency', 'Select currency :', $currencies));

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

class PaymentProcessor_MerchantHosted extends PaymentProcessor {

  public function capture($data) {
    parent::capture($data);

    $result = $this->gateway->process($this->paymentData);

    // $result will be PaymentGateway_Result, check status
    // Update the payment status with HTTPStatus etc.
    // Update the payment as success or failure etc.
    $HTTPStatus = $result->getHTTPResponse()->getStatusCode();
    $this->payment->updateStatus(
      $result->getStatus(),
      $result->getHTTPResponse()->getStatusCode(),
      $result->getMessage(),
      $result->getErrors()
    );

    // Do redirection
    $this->doRedirect();
  }

  public function getCreditCardFields() {
    // Retrieve the array of credit card types to be put in a credit card form field
    $creditCardTypes = $this->gateway->getSupportedCreditCardType();
    $creditCardTypeDisplays = CreditCard::getCreditCardTypeDisplays($creditCardTypes);

    $months = array_combine(range(1, 12), range(1, 12));
    $years = array_combine(range(date('Y'), date('Y') + 10), range(date('Y'), date('Y') + 10));

    $fieldList = new FieldList();
    $fieldList->push(new DropDownField('CreditCardType', 'Select Credit Card Type :', $creditCardTypeDisplays));
    $fieldList->push(new TextField('FirstName', 'First Name:'));
    $fieldList->push(new TextField('LastName', 'Last Name:'));
    $fieldList->push(new CreditCardField('CardNumber', 'Credit Card Number:'));
    $fieldList->push(new DropDownField('MonthExpiry', 'Expiration Month: ', $months));
    $fieldList->push(new DropDownField('YearExpiry', 'Expiration Year: ', $years));
    $fieldList->push(new TextField('Cvc2', 'Credit Card CVN: (3 or 4 digits)', '', 4));

    return $fieldList;
  }

  public function getFormFields() {
    $fieldList = parent::getFormFields();
    $fieldList->merge($this->getCreditCardFields());

    return $fieldList;
  }

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

class PaymentProcessor_GatewayHosted extends PaymentProcessor {

  public function capture($data) {

    parent::capture($data);

    // Set the return link
    $returnURL = Director::absoluteURL(Controller::join_links(
        $this->link(),
        'complete',
        $this->methodName,
        $this->payment->ID
    ));
    $this->gateway->setReturnURL($returnURL);

    // Send a request to the gateway
    $result = $this->gateway->process($this->paymentData);

    // Processing may not get to here if all goes smoothly, customer will be at the 3rd party gateway
    // Get the errors from the
    if ($result && !$result->isSuccess()) {
      // Gateway did not respond or respond with error
      // Need to save the gateway response and save HTTP Status, errors etc. to Payment
      $this->payment->updateStatus(
        Payment::FAILURE,
        $result->getHTTPResponse()->getStatusCode(),
        $result->getMessage(),
        $result->getErrors()
      );

      // Payment has failed - redirect to confirmation page
      // Developers can get the failure data from the database to show
      // the proper errors to users
      $this->doRedirect();
    }
  }

  /**
   * Process a payment response.
   *
   * @param SS_HTTPResponse $request. This is the object representation for
   * the http request received from the gateway. This request will be forwarded
   * to gateway->getResult() for gateway-specific processing
   */
  public function complete($request) {
    // Retrieve the payment object if none is referenced at this point
    if (! $this->payment) {
      $this->payment = $this->getPaymentObject($request);
    }

    // Reconstruct the gateway object
    $methodName = $request->param('ID');
    $this->gateway = PaymentFactory::get_gateway($methodName);

    // Query the gateway for the payment result
    $result = $this->gateway->getResponse($request);
    $this->payment->updateStatus(
      $result->getStatus(),
      $result->getHTTPResponse()->getStatusCode(),
      $result->getMessage(),
      $result->getErrors()
    );

    // Do redirection
    $this->doRedirect();
  }

  public function getPaymentObject($request) {
    return DataObject::get_by_id('Payment', $request->param('OtherID'));
  }
}