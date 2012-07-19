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
   * #var array 
   */
  public $paymentData;

  /**
   * If this is set to some url value, the processor will redirect 
   * to the url after a payment finishes processing.
   */
  public $postProcessRedirect = null;
  
  /**
   * Get the supported methods array set by the yaml configuraion
   */
  public static function get_supported_methods() {
    $supported_methods = Config::inst()->get('PaymentProcessor', 'supported_methods');

    // Check if all methods are defined in factory
    foreach ($supported_methods as $method) {
      if (! PaymentFactory::get_factory_config($method)) {
        user_error("Method $method not defined in factory", E_USER_ERROR);
      }
    }

    return $supported_methods;
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
   * Perform some actions before the payment is processed
   */
  public function preProcess() { 
    // Save preliminary data to database
    $this->payment->Amount->Amount = $this->paymentData['Amount'];
    $this->payment->Amount->Currency = $this->paymentData['Currency'];
    $this->payment->Status = Payment::PENDING;
    $this->payment->Method = $this->methodName;
    $this->payment->write();
  }
  
  /**
   * Process a payment request.
   *
   * @param $data
   * @return Payment
   */
  public function processRequest($data) {
    $this->paymentData = $data;
    
    // Do pre-processing
    $this->preProcess();
    
    // Do gateway validation of payment data
    $this->gateway->validatePaymentData($this->paymentData);
    
    if (! $this->gateway->validationResult->valid()) {
      user_error('Payment data is not valid', E_USER_ERROR);
    }
  }

  /**
   * Process a payment response.
   */
  public function processresponse($response) {
    // Get the reponse result from gateway
    $result = $this->gateway->getResponse($response);

    // Retrieve the payment object if none is referenced at this point
    if (! $this->payment) {
      $this->payment = $this->getPaymentObject($response);
    }
    
    // Save gateway message
    $this->payment->Message = $result->getMessage();
    
    // Save payment status
    switch ($result->getStatus()) {
      case PaymentGateway_Result::SUCCESS:
        $this->payment->updatePaymentStatus(Payment::SUCCESS);
        break;
      case PaymentGateway_Result::FAILURE;
      $this->payment->updatePaymentStatus(Payment::FAILURE);
      break;
      case PaymentGateway_Result::INCOMPLETE;
      $this->payment->updatePaymentStatus(Payment::INCOMPLETE);
      break;
      default:
        break;
    }
    
    // Do post-processing
    return $this->postProcess();
  }

  /**
   * Helper function to get the payment object from the gateway response
   */
  public function getPaymentObject($response) { }

  /**
   * Perform an action after the payment is processed.
   * If $postProcessRedirect is set, redirect to the url. If not,
   * render a default page to show payment result.
   */
  public function postProcess() {
    if ($this->postProcessRedirect) {
      // Put the payment ID in a session
      Session::set('PaymentID', $this->payment->ID);
      Controller::curr()->redirect($this->postProcessRedirect);
    } else {
      if ($this->payment) {
        return $this->customise(array(
          "Content" => 'Payment #' . $this->payment->ID . ' status:' . $this->payment->Status,
          "Form" => '',
        ))->renderWith("Page");
      } else {
        return null;
      }
    }
  }

  /**
   * Get the processor's form fields. Custom controllers use this function
   * to add the form fields specifically to gateways.
   *
   * return FieldList
   */
  public function getFormFields() {
    $fieldList = new FieldList();

    $fieldList->push(new NumericField('Amount', 'Amount', ''));
    $fieldList->push(new TextField('Currency', 'Currency', 'NZD'));

    return $fieldList;
  }
  
  /**
   * Get the form requirements
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
      'number' => $this->paymentData['CardNumber']
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