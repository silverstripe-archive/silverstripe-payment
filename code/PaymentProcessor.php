<?php

/**
 * Default class for a number of payment controllers.
 * This acts as a generic controller for all payment methods.
 * Override this class if desired to add custom functionalities.
 *
 * Configuration format for PaymentProcessor:
 * PaymentProcessor:
 *   supported_methods:
 *     {method name}:
 *       {controller name}
 *   gateway_classes:
 *     {environment}:
 *       {gateway class name}
 *
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
   * The url that will be redirected to after processing the payment
   * 
   * @var String
   */
  protected $redirectURL;
  
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
    $this->redirectURL = $url;
  }
  
  /**
   * Save preliminary data to database before processing payment
   */
  public function preProcess() { 
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
  public function processRequest($data) {
    $this->paymentData = $data;
    
    // Do pre-processing
    $this->preProcess();
    
    // Do gateway validation of payment data
    $validation = $this->gateway->validatePaymentData($this->paymentData);
    
    if (! $this->gateway->validationResult->valid()) {
      throw new ValidationException($validation, "Payment data validation error: " . $validation->message(), E_USER_WARNING);
    }
  }

  /**
   * Process a payment response. 
   * 
   * @param SS_HTTPResponse $response
   */
  public function processresponse($response) {
    // Check the HTTP response status 
    $statusCode = $response->getStatusCode();
    if ($statusCode != '200') {
      // HTTP fails. Stop the payment and save the code to database
      // TODO: Log the error for developers to troubleshoot
      $this->payment->HTTPStatus = $statusCode;
      $this->payment->Status = Payment::FAILURE;
      $this->payment->write();
    }
    
    // Get the reponse result from gateway
    $result = $this->gateway->getResponse($response);

    // Retrieve the payment object if none is referenced at this point
    if (! $this->payment) {
      $this->payment = $this->getPaymentObject($response);
    }
    
    // Save gateway message
    $this->payment->Message = $result->message();
    
    // Save payment status
    switch ($result->getStatus()) {
      case PaymentGateway_Result::SUCCESS:
        $status = Payment::SUCCESS;
        break;
      case PaymentGateway_Result::FAILURE:
        $status = Payment::FAILURE;
        break;
      case PaymentGateway_Result::INCOMPLETE:
        $status = Payment::INCOMPLETE;
        break;
      default:
        $status = 'invalid';
        break;
    }
    if (! $this->payment->updatePaymentStatus($status)) {
      throw new Exception('Invalid payment status');
    }
    
    // Save messages and error codes if any
    if ($this->gateway->gatewayResult) {
      $this->payment->Message = $this->gateway->gatewayResult->message();      
      $this->payment->ErrorCodes = implode('; ', $this->gateway->getwayResult->codeList());
      $this->payment-write();
    }
    
    // Do post-processing
    $this->redirectPostProcess();
  }

  /**
   * Helper function to get the payment object from the gateway response.
   * To be implemented by subclasses.
   * 
   * @return PaymentGateway
   */
  public function getPaymentObject($response) { }

  /**
   * Redirection for post-processing
   */
  public function redirectPostProcess() {
    // Put the payment ID in a session
    Session::set('PaymentID', $this->payment->ID);
    Controller::curr()->redirect($this->redi);
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
    $currencies = $this->gateway->getSupportedCurrencies();
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
  
  public function preProcess() {
    parent::preProcess();
    
    // Construct a credit card object and add to the payment data
    $options = array(
      'firstName' => $this->paymentData['FirstName'],
      'lastName' => $this->paymentData['LastName'],
      'month' => date('n', strtotime($this->paymentData['DateExpiry'])),
      'year' => date('m', strtotime($this->paymentData['DateExpiry'])),
      'type' => $this->paymentData['CreditCardType'],
      'number' => implode('', $this->paymentData['CardNumber'])
    );
    
    $this->paymentData['CreditCard'] = new CreditCard($options);    
  }

  public function processRequest($data) {
    parent::processRequest($data);
    
    // Call processResponse directly since there's no need to set return link
    $response = $this->gateway->process($this->paymentData);
    return $this->processresponse($response);
  }
  
  public function getPaymentObject($response) {
    return $this->payment;
  }
  
  public function getCreditCardFields() {
    $fieldList = new FieldList();
    
    $creditCardTypes = $this->gateway->getSupportedCreditCardType();
    $fieldList->push(new DropDownField('CreditCardType', 'Select Credit Card Type :', $creditCardTypes));
    $fieldList->push(new TextField('FirstName'), 'First Name :');
    $fieldList->push(new TextField('LastName'), 'Last Name :');
    $fieldList->push(new CreditCardField('CardNumber', 'Credit Card Number :'));
    $fieldList->push(new TextField('DateExpiry', 'Credit Card Expiry : (MMYY)', '', 4));
    $fieldList->push(new TextField('Cvc2', 'Credit Card CVN : (3 or 4 digits)', '', 4));
    
    return $fieldList;
  }

  public function getFormFields() {
    $fieldList = parent::getFormFields();
    $fieldList->merge($this->getCreditCardFields());

    return $fieldList;
  }
  
  public function getFormRequirements() {
    $required = parent::getFormRequirements();
    $required->appendRequiredFields(new RequiredFields('FirstName', 'LastName', 'CardNumber', 'DateExpiry', 'Cvc2'));
  }
}

class PaymentProcessor_GatewayHosted extends PaymentProcessor {

  public function processRequest($data) {
    parent::processRequest($data);

    // Set the return link
    // TODO: Allow custom return url
    $returnURL = Director::absoluteURL(Controller::join_links(
        $this->link(),
        'processresponse',
        $this->methodName,
        $this->payment->ID));
    $cancelURL = Director::absoluteURL(Controller::join_links(
        $this->link(),
        'cancel',
        $this->methodName,
        $this->payment->ID));
    $this->gateway->setReturnURL($returnURL);
    $this->gateway->setCancelURL($cancelURL);

    // Send a request to the gateway
    $this->gateway->process($this->paymentData);
  }

  public function processresponse($response) {
    // Reconstruct the gateway object
    $this->setMethodName($response->param('ID'));
    $this->gateway = PaymentFactory::get_gateway($this->methodName);

    return parent::processresponse($response);
  }
  
  public function cancel($response) {
    return $this->processresponse(new PaymentGateway_Result(PaymentGateway_Result::INCOMPLETE));
  }

  public function getPaymentObject($response) {
    return DataObject::get_by_id('Payment', $response->param('OtherID'));
  }
}