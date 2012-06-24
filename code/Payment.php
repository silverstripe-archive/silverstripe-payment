<?php 

/**
 * "Abstract" class for a number of payment models
 * 
 *  @package payment
 */
class Payment extends DataObject {
  /**
   * Constants for payment statuses
   */
  const SUCCESS = 'Success';
  const FAILURE = 'Failure';
  const INCOMPLETE = 'Incomplete';
  const PENDING = 'Pending';
  
  public function getFormRequirements() {
    if (!$this->requiredFormFields) {
      $this->requiredFormFields = array();
    }
    
    return $this->requiredFormFields;
  }
  
  /**
   * Incomplete (default): Payment created but nothing confirmed as successful
   * Success: Payment successful
   * Failure: Payment failed during process
   * Pending: Payment awaiting receipt/bank transfer etc
   * Incomplete: Payment cancelled
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
   * @param String $status
   */
  public function updatePaymentStatus($status) {
    if ($status == self::SUCCESS || $status == self::FAILURE || 
        $status == self::INCOMPLETE || $status == self::PENDING) {
      $this->Status = $status;
      $this->write();
    } else {
      user_error("Invalid payment status", E_USER_ERROR);
    }
  }
}

/**
 *  Interface for a payment gateway controller
 */
interface Payment_Controller_Interface {

  public function processRequest($form, $data);
  public function processresponse($response);
  public function getCustomFormFields();
}

/**
 * Default class for a number of payment controllers.
 * This acts as a generic controller for all payment methods.
 * Override this class if desired to add custom functionalities.
 * 
 * Configuration format for Payment_Controller:
 * Payment_Controller:
 *   supported_methods:
 *     {method name}:
 *       {controller name}
 *   gateway_classes:
 *     {environment}:
 *       {gateway class name}
 * 
 */
class Payment_Controller extends Controller implements Payment_Controller_Interface {
  /**
   * The method name of this controller
   */
  private $methodName;

  /**
   * The payment object to be injected to this controller
   */
  public $payment;
  
  /**
   * The gateway object to be injected to this controller
   */
  public $gateway;
  
  /**
   * Get the supported methods set by the yaml configuraion
   */
  public static function get_supported_methods() {
    return Config::inst()->get('Payment_Controller', 'supported_methods');
  }

  public function __construct() {
    parent::__construct();
    
    // Set the dependencies
    $this->gateway = $this->getGateway();
    $this->payment = $this->getPayment();
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
    if (! $this->methodName || $this->methodName == 'Payment') {
      return new Payment_Gateway();
    }
    
    // Get the gateway environment setting
    $environment = Config::inst()->get('Payment_Gateway', 'environment');
    
    // Get the custom class configuration if applicable.  
    // If not, apply naming convention.
    $classConfig = Config::inst()->get($this->class, 'gateway_classes');
    if (isset($classConfig[$environment])) {
      $gatewayClass = $classConfig[$environment];
    } else {
      switch($environment) {
        case 'live':
          $gatewayClass = $this->methodName . '_Production_Gateway';
          break;
        case 'dev':
          $gatewayClass = $this->methodName . '_Sandbox_Gateway';
          break;
        case 'test':
          $gatewayClass = $this->methodName . '_Mock_Gateway';
          break;
      }
    }
    
    if (class_exists($gatewayClass)) {
      return new $gatewayClass();
    } else {
      user_error("Gateway class does not exists.", E_USER_ERROR);
    }
  }
  
  /**
   * Get the payment object that will be used by this controller.
   * The payment class is automatically retrieved based on naming convention.
   */
  protected function getPayment() {
    if (! $this->methodName || $this->methodName == 'Payment') {
      return new Payment();
    }
    
    $paymentClass = $this->methodName;
    if (class_exists($paymentClass)) {
      return new $paymentClass();
    } else {
      user_error("Payment class does not exists.", E_USER_ERROR);
    }
  }

  /**
   * Process a payment request. 
   *
   * @param $data
   * @return Payment
   */
  public function processRequest($form, $data) {
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
    
    // Retrieve the payment object
    $payment = DataObject::get_by_id('Payment',$request->param('ID'));
    
    switch ($result->getStatus()) {
      case Payment_Gateway_Result::SUCCESS:
        $payment->updatePaymentStatus(Payment::SUCCESS);
        break;
      case Payment_Gateway_Result::FAILURE;
        $payment->updatePaymentStatus(Payment::FAILURE);
        break;
      case Payment_Gateway_Result::INCOMPLETE;
        $payment->updatePaymentStatus(Payment::INCOMPLETE);
        break;
      default:
        break;
    }
    
    // Render a default page
    return $this->renderPostProcess();
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
    foreach (singleton('Payment_Controller')->getDefaultFormFields() as $field) {
      $fieldList->push($field);
    }
    
    // Custom form fields for each gateway
    foreach (self::get_supported_methods() as $methodName => $controllerClass) {
      if ($controllerClass != 'Payment_Controller') {
        $controller = singleton($controllerClass);
        $controller->setMethodName($methodName);
        foreach ($controller->getCustomFormFields() as $field) {
          $fieldList->push($field);
        }
      }
    }
    
    return $fieldList;
  }
}

class Payment_Controller_MerchantHosted extends Payment_Controller implements Payment_Controller_Interface {

  public function processRequest($form, $data) {
    parent::processRequest($form, $data);
    
    // Call processResponse directly since there's no need to set return link
    $response = $this->gateway->process($data);
    $this->processresponse($response);
  }

  public function processresponse($response) {
    parent::processResponse($response);
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

class Payment_Controller_GatewayHosted extends Payment_Controller implements Payment_Controller_Interface {

  public function processRequest($form, $data) {
    parent::processRequest($form, $data);
    
    // Set the return link for external gateway
    $this->gateway->setReturnURL(Controller::join_links($this->$URLSegment, 'response', $this->payment->ID));
    
    // Send a request to the gateway 
    $this->gateway->process($data);
  }

  public function processresponse($response) {
    parent::processresponse($response);
  }

  public function getCustomFormFields() {
    return parent::getCustomFormFields();
  }
}

/**
 * Abstract class for a number of payment gateways
*
* @package payment
*/
class Payment_Gateway {
  /**
   * The gateway url
   */
  protected $gatewayURL;

  /**
   * The link to return to after processing payment
   */
  protected  $returnURL;

  /**
   * Get the gateway type set by the yaml config ('live', 'dev', 'mock')
   */
  public static function get_type() {
    return Config::inst()->get('Payment_Gateway', 'type');
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
  public function process($data) {
    user_error("Please implement process() on $this->class", E_USER_ERROR);
  }

  /**
   * Process the response from the external gateway
   *
   * @return Payment_Gateway_Result
   */
  public function getResponse($response) {
    return new Payment_Gateway_Result(Payment_Gateway_Result::FAILURE);
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
class Payment_Gateway_Result {

  const SUCCESS = 'Success';
  const FAILURE = 'Failure';
  const INCOMPLETE = 'Incomplete';

  protected $status;

  function __construct($status = null) {
    if ($status == self::SUCCESS || $status == self::FAILURE || $status == self::INCOMPLETE) {
      $this->status = $status;
    } else {
      user_error("Invalid result status", E_USER_ERROR);
    }
  }

  function getStatus() {
    return $this->status;
  }
}