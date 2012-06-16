<?php 

/**
 * "Abstract" class for a number of payment models
 * 
 *  @package payment
 */
class Payment extends DataObject {
  /**
   * The payment form fields that should be shown on the checkout order form
   */
  public $formFields;
  public $requiredFormFields;
  
  /**
   * The payment class in use
   */
  protected static $payment_class;
  
  public static function set_payment_class($class) {
    if (class_exists($class)) {
      self::$payment_class = $class;
    } 
  }
  
  public static function get_payment_class() {
    return self::$payment_class;
  }
  
  public function getFormFields() {
    if (!$this->formFields) {
      $this->formFields = new FieldList();
    }
    
    return $this->formFields;
  }
  
  public function getFormRequirements() {
    if (!$this->requiredFormFields) {
      $this->requiredFormFields = array();
    }
    
    return $this->requiredFormFields;
  }
  
  /**
   * Get the payment gateway class name from the associating module name
   * 
   * @param $gatewayName
   * @return gateway class name
   * 
   * TODO: Generalize naming convention
   *       Take care of merchant hosted and gateway hosted sub classes
   */
  public static function payment_class_name($gatewayname) {
    $className = $gatewayname . "_Payment";
    if (class_exists($className)) {
      return $className;
    } 
    
    return null;
  }
  
  /**
   * Incomplete (default): Payment created but nothing confirmed as successful
   * Success: Payment successful
   * Failure: Payment failed during process
   * Pending: Payment awaiting receipt/bank transfer etc
   */
  public static $db = array(
    'Status' => "Enum('Incomplete, Success, Failure, Pending')",
    'Amount' => 'Money',
    'Message' => 'Text',
  );
  
  public static $has_one = array(
    'PaidFor' => 'Object',
    'PaidBy' => 'Member',
  );
  
  /**
   * Make payment table transactional.
   */
  static $create_table_options = array(
    'MySQLDatabase' => 'ENGINE=InnoDB'
  );
  
  /**
   * Update the status of this payment
   *
   * @param $status
   */
  public function updatePaymentStatus($status) {
    $payment->Status = $status;
    $payment->write();
  }
}

/**
 *  Interface for a payment gateway controller
 */
interface Payment_Controller_Interface {

  public function processRequest($data);
  public function processResponse($response);
  public function getFormFields();
}

/**
 * Abstract class for a number of payment controllers.
 */
class Payment_Controller extends Controller implements Payment_Controller_Interface {

  static $URLSegment;
  
  /**
   * The controller class in use
   */
  protected static $controller_class;
  
  /**
   * Type of the controller, merchant_hosted or gateway_hosted
   * @var string
   */
  protected static $type = 'gateway_hosted';

  /**
   * The payment object to be injected to this controller
   */
  public $payment;
  
  /**
   * The gateway object to be injected to this controller
   */
  public $gateway;
  
  public static function set_controller_class($class) {
    if (class_exists($class)) {
      self::$controller_class = $class;
    }
  }
  
  public static function get_controller_class() {
    return self::$controller_class;
  }
  
  public static function set_type($type) {
    if ($type == 'merchant_hosted' || $type == 'gateway_hosted') {
      self::$type = $type;
    } 
  }

  /**
   * Construct a controller instance with injected payment and gateway objects
   */
  public static function init_instance($payment, $gateway) {
    $class = get_called_class();
    $instance = new $class();
    self::set_controller_class($class);
      
    $instance->payment = $payment;
    Payment::set_payment_class($payment->class);      
    $instance->gateway = $gateway;
    Payment_Gateway::set_gateway_class($gateway->class);
      
    return $instance;
  }

  /**
   * Get the controller's class name from a given gateway module name and config
   * TODO: Generalize naming convention; make use of _config.php
   *
   * @param $gatewayName
   * @return Controller class name
   */
  public static function controller_class_name($gatewayName) {
    if (! self::$type) {
      return null;
    }
    
    switch (self::$type) {
      case 'merchant_hosted':
        $controllerClass = $gatewayName . "_MerchantHosted_Controller";
        break;
      case 'gateway_hosted':
        $controllerClass = $gatewayName . "_GatewayHosted_Controller";
        break;
      default: 
        $controllerClass = $gatewayName . "_Controller";
        break;
    }
    
    if (class_exists($controllerClass)) {
      return $controllerClass;
    } else {
      return null;
    }
  }

  /**
   * Process a payment request. Subclasses must call this parent function when overriding
   *
   * @param $data
   */
  public function processRequest($data) {
    if (! isset($data['Amount'])) {
      user_error("Payment amount not set!", E_USER_ERROR);
    } else {
      $amount = $data['Amount'];
    }

    if (! isset($data['Currency'])) {
      $currency = $data['Currency'];
    } else {
      //$currency = $this->payment->site_currency();
    }

    // Save preliminary data to database
    $this->payment->Amount->Amount = $amount;
    $this->payment->Amount->Currency = $currency;
    $this->payment->Status = 'Pending';
    $this->payment->write();
    
    // Send a request to the gateway 
    $result = $this->gateway->process($data);
    if ($result->isSuccess()) {
      $this->payment->updatePaymentStatus('Success');
    } else if (! $result->isSuccess() && ! $result->isProcessing()) {
      $this->payment->updatePaymentStatus('Failure');
    }
  }

  /**
   * Process a payment response, to be implemented by specific gateways.
   */
  public function processResponse($response) {
    user_error("Please implement processResponse() on $this->class", E_USER_ERROR);
  }
  
  /**
   * Get the form fields to be shown at the checkout page
   */
  public function getFormFields() { 
    return new FieldList();
  }
}

class Payment_Controller_MerchantHosted extends Payment_Controller implements Payment_Controller_Interface {

  public function processRequest($data){
    parent::processRequest($data);
  }

  public function processResponse($response){
    parent::processResponse($response);
  }

  public function getFormFields() {
    $fieldList = parent::getFormFields();
    $fieldList->push(new TextField('CardHolderName', 'Credit Card Holder Name :'));
    $fieldList->push(new CreditCardField('CardNumber', 'Credit Card Number :'));
    $fieldList->push(new TextField('DateExpiry', 'Credit Card Expiry : (MMYY)', '', 4));
    $fieldList->push(new TextField('Cvc2', 'Credit Card CVN : (3 or 4 digits)', '', 4));
    
    return $fieldList;
  }
}

class Payment_Controller_GatewayHosted extends Payment_Controller implements Payment_Controller_Interface {

  public function processRequest($data){
    parent::processRequest($data);
  }

  public function processResponse($response){
    parent::processResponse($response);
  }

  public function getFormFields(){
    return parent::getFormFields();
  }
}

/**
 * Factory class to allow easy initiation of payment objects
 */
class PaymentFactory {
  /**
   * Construct an instance of the desired payment controller
   *
   * @param $gatewayName
   * @return PaymentProcessor
   */
  public static function createController($gatewayName) {
    if ($paymentClass = Payment::payment_class_name($gatewayName)) {
      $payment = new $paymentClass();
    } else {
      user_error("Payment class does not exists.", E_USER_ERROR);
    }
    
    if ($gatewayClass = Payment_Gateway::gateway_class_name($gatewayName)) {
      $gateway = new $gatewayClass();
    } else {
      user_error("Gateway class does not exists.", E_USER_ERROR);
    }
    
    if ($controllerClass = Payment_Controller::controller_class_name($gatewayName)) {
      return $controllerClass::init_instance($payment, $gateway);
    } else {
      user_error("Controller class does not exists.", E_USER_ERROR);
    }
  }
}