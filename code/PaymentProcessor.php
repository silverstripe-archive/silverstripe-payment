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
   */
  protected $methodName;

  /**
   * The payment object to be injected to this controller
   */
  public $payment;

  /**
   * The gateway object to be injected to this controller
   */
  public $gateway;

  /**
   * Get the supported methods array set by the yaml configuraion
   */
  public static function get_supported_methods() {
    $supported_methods = Config::inst()->get('PaymentProcessor', 'supported_methods');

    // Check if all methods are defined in factory
    foreach ($supported_methods as $method) {
      if (! self::get_factory_config($method)) {
        user_error("Method $method not defined in factory", E_USER_ERROR);
      }
    }

    return $supported_methods;
  }

  /**
   * Get the factory config for a particular payment method
   *
   * @param String methodName
   */
  public static function get_factory_config($methodName) {
    $factoryConfig = Config::inst()->get('PaymentProcessor', 'factory');
    if (isset($factoryConfig[$methodName])) {
      return $factoryConfig[$methodName];
    } else {
      return null;
    }
  }

  /**
   * Get the controller class for a particular gateway
   */

  /**
   * Factory function to create payment controller object
   *
   * @param String $methodName
   */
  public static function factory($methodName) {
    $supported_methods = self::get_supported_methods();

    if (! in_array($methodName, $supported_methods)) {
      user_error("The method $methodName is not supported.");
    }

    $methodConfig = self::get_factory_config($methodName);
    if (isset($methodConfig['controller'])) {
      $controllerClass = $methodConfig['controller'];
      $controller = new $controllerClass();
      $controller->setMethodName($methodName);

      // Set the dependencies
      $controller->gateway = $controller->getGateway();
      $controller->payment = $controller->getPayment();

      return $controller;
    } else {
      user_error("No controller is defined for the method $methodName");
    }
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
   * Get the gateway object that will be used by this controller.
   * The gateway class is automatically retrieved based on configuration
   */
  protected function getGateway() {
    if (! $this->methodName) {
      return null;
    }

    // Get the gateway environment setting
    $environment = PaymentGateway::get_environment();

    // Get the custom class configuration if applicable.
    // If not, apply naming convention.
    $methodConfig = self::get_factory_config($this->methodName);
    $gatewayClassConfig = $methodConfig['gateway_classes'];
    if (isset($gatewayClassConfig[$environment])) {
      $gatewayClass = $gatewayClassConfig[$environment];
    } else {
      switch($environment) {
        case 'live':
          $gatewayClass = $this->methodName . '_Gateway_Production';
          break;
        case 'dev':
          $gatewayClass = $this->methodName . '_Gateway_Dev';
          break;
        case 'test':
          $gatewayClass = $this->methodName . '_Gateway_Mock';
          break;
      }
    }

    if (class_exists($gatewayClass)) {
      return new $gatewayClass();
    } else {
      user_error("$gatewayClass class does not exists.", E_USER_ERROR);
    }
  }

  /**
   * Get the payment object that will be used by this controller.
   * The payment class is automatically retrieved based on naming convention.
   */
  protected function getPayment() {
    if (! $this->methodName) {
      return null;
    }

    // Get the custom payment class configuration.
    // If not applicable, apply naming convention
    $methodConfig = self::get_factory_config($this->methodName);
    if (isset($methodConfig['model'])) {
      $paymentClass = $methodConfig['model'];
    } else {
      $paymentClass = $this->methodName;
    }

    if (class_exists($paymentClass)) {
      return new $paymentClass();
    } else {
      user_error("$paymentClass class does not exists.", E_USER_ERROR);
    }
  }

  /**
   * Process a payment request.
   *
   * @param $data
   * @return Payment
   */
  public function processRequest($data) {
    // Save preliminary data to database
    $this->payment->Amount->Amount = $data['Amount'];
    $this->payment->Amount->Currency = $data['Currency'];
    $this->payment->Status = Payment::PENDING;
    $this->payment->write();
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

    // Render a default page
    return $this->renderPostProcess();
  }

  /**
   * Helper function to get the payment object from the gateway response
   */
  public function getPaymentObject($response) {
  }

  /**
   * Render a page for showing payment status after finish processing.
   * Can be overriden if desired.
   */
  public function renderPostProcess() {
    if ($this->payment) {
      return $this->customise(array(
          "Content" => 'Payment #' . $this->payment->ID . ' status:' . $this->payment->Status,
          "Form" => '',
      ))->renderWith("Page");
    } else {
      return null;
    }
  }

  /**
   * Get the default form fields to be shown at the checkout page
   *
   * return FieldList
   */
  public function getDefaultFormFields() {
    $fieldList = new FieldList();

    $fieldList->push(new NumericField('Amount', 'Amount', ''));
    $fieldList->push(new TextField('Currency', 'Currency', 'NZD'));

    return $fieldList;
  }

  /**
   * Get the custom form fields. Custom controllers use this function
   * to add the form fields specifically to gateways.
   *
   * return FieldList
   */
  public function getCustomFormFields() {
    return new FieldList();
  }

  /**
   * Return a list of combined form fields from all supported payment methods
   *
   * @return FieldList
   */
  public static function get_combined_form_fields() {
    $fieldList = new FieldList();

    // Add the default form fields
    foreach (singleton('PaymentProcessor')->getDefaultFormFields() as $field) {
      $fieldList->push($field);
    }

    // Custom form fields for each gateway
    foreach (self::get_supported_methods() as $methodName) {
      $controller = self::factory($methodName);
      foreach ($controller->getCustomFormFields() as $field) {
        $fieldList->push($field);
      }
    }

    return $fieldList;
  }
}

class PaymentProcessor_MerchantHosted extends PaymentProcessor {

  public function processRequest($data) {
    parent::processRequest($data);

    // Call processResponse directly since there's no need to set return link
    $response = $this->gateway->process($data);
    return $this->processresponse($response);
  }

  public function processresponse($response) {
    return parent::processResponse($response);
  }

  public function getPaymentObject($response) {
    return $this->payment;
  }

  public function getCustomFormFields() {
    $fieldList = parent::getCustomFormFields();
    $fieldList->push(new TextField('CardHolderName', 'Credit Card Holder Name :'));
    $fieldList->push(new CreditCardField('CardNumber', 'Credit Card Number :'));
    $fieldList->push(new TextField('DateExpiry', 'Credit Card Expiry : (MMYY)', '', 4));
    $fieldList->push(new TextField('Cvc2', 'Credit Card CVN : (3 or 4 digits)', '', 4));

    return $fieldList;
  }
}

class PaymentProcessor_GatewayHosted extends PaymentProcessor {

  public function processRequest($data) {
    parent::processRequest($data);

    // Set the return link
    $returnURL = Director::absoluteURL(Controller::join_links(
        $this->link(),
        'payment',
        $this->methodName,
        'processresponse',
        $this->payment->ID));
    $this->gateway->setReturnURL($returnURL);

    // Send a request to the gateway
    $this->gateway->process($data);
  }

  public function processresponse($response) {
    // Reconstruct the gateway object
    $this->setMethodName($response->param('MethodName'));
    $this->gateway = $this->getGateway();

    return parent::processresponse($response);
  }

  public function getPaymentObject($response) {
    return DataObject::get_by_id('Payment', $response->param('ID'));
  }

  public function getCustomFormFields() {
    return parent::getCustomFormFields();
  }
}