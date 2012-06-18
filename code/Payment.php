<?php 

/**
 * "Abstract" class for a number of payment models
 * 
 *  @package payment
 */
class Payment extends DataObject {
  
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
    $this->Status = $status;
    $this->write();
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
 * Default class for a number of payment controllers.
 * This acts as a generic controller for all payment methods.
 * Override this class if desired to add custom functionalities.
 */
class Payment_Controller extends Controller implements Payment_Controller_Interface {

  /**
   * The payment method to be used by this controller 
   */
  public static $paymentMethod = 'Dummy';

  /**
   * The payment object to be injected to this controller
   */
  public $payment;
  
  /**
   * The gateway object to be injected to this controller
   */
  public $gateway;

  public function __construct() {
    parent::__construct();
  
    //Set the dependencies
    $this->gateway = $this->getGateway();
    $this->payment = $this->getPayment();
  }
  
  /**
   * Get the gateway object that will be used by this controller.
   * The gateway class is automatically retrieved based on configuration
   */
  private function getGateway() {
    if ($gatewayClass = Payment_Gateway::gateway_class_name(self::$paymentMethod)) {
      $gateway = new $gatewayClass();
    } else {
      user_error("Gateway class does not exists.", E_USER_ERROR);
    }

    return $gateway;
  }
  
  /**
   * Get the payment object that will be used by this controller.
   * The payment class is automatically retrieved based on configuration
   */
  private function getPayment() {
    if ($paymentClass = Payment::payment_class_name(self::$paymentMethod)) {
      $payment = new $paymentClass();
    } else {
      user_error("Payment class does not exists.", E_USER_ERROR);
    }
    
    return $payment;
  }

  /**
   * Process a payment request. Subclasses must call this parent function when overriding
   *
   * @param $data
   * @return Payment
   */
  public function processRequest($data) {
    if (! isset($data['Amount'])) {
      user_error("Payment amount not set!", E_USER_ERROR);
    } else {
      $amount = $data['Amount'];
    }

    if (isset($data['Currency'])) {
      $currency = $data['Currency'];
    } else {
      //$currency = $this->payment->site_currency();
    }

    // Save preliminary data to database
    $this->payment->Amount->Amount = $amount;
    $this->payment->Amount->Currency = $currency;
    $this->payment->Status = 'Pending';
    $this->payment->write();
    
    // Set the return link for external gateway
    $this->gateway->setReturnURL(Controller::join_links($this->$URLSegment, 'response', $this->payment->ID));
    
    // Send a request to the gateway 
    $this->gateway->process($data);
  }

  /**
   * Process a payment response, to be implemented by specific gateways.
   */
  public function processresponse($response) {
    // Get the reponse result from gateway
    $result = $this->gateway->getResponse($response);
    
    // Retrieve the payment object
    $payment = DataObject::get_by_id('Payment',$request->param('ID'));
    
    switch ($result->getStatus()) {
      case Gateway_Result::SUCCESS:
        $payment->updatePaymentStatus('Success');
        break;
      case Gateway_Result::FAILURE;
        $payment->updatePaymentStatus('Failure');
        break;
      case Gateway_Result::INCOMPLETE;
        $payment->updatePaymentStatus('Incomplete');
        break;
      default:
        break;
    }
    
    // Render a default page
    return $this->renderPostProcess();
  }
  
  /**
   * Render a page for showing payment status after finish processing
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
   * Get the form fields to be shown at the checkout page
   */
  public function getFormFields() { 
    $fieldList = new FieldList();

    $fieldList->push(new HiddenField('PaymentMethod', 'Payment Method', $this->class));
    $fieldList->push(new NumericField('Amount', 'Amount', ''));
    $fieldList->push(new TextField('Currency', 'Currency', 'NZD'));

    return $fieldList;
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