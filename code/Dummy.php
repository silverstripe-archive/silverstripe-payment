<?php

/**
 * Test class for merchant-hosted payment
 */
class DummyMerchantHostedGateway extends PaymentGateway {
  
  public function getSupportedCreditCardType() {
    return (array('master' => 'Master', 'visa' => 'Visa'));
  }
  
  /**
   * Override to cancel data validation
   * 
   * @see PaymentGateway::validate()
   * 
   * @param Array $data
   * @return ValidationResult
   */
  public function validate($data) {

    //Use this->validationResult so that all errors are added and can be accessible from Payment Test
    //TODO this should do actual validation of the data 

    $result = $this->getValidationResult();
    $amount = $data['Amount'];
    $cents = round($amount - intval($amount), 2);
    
    switch ($cents) {
      case 0.11:
        $result->error('Cents value is .11');
        $result->error('This is another error message for cents = .11');
        break;
    }
    return $result;
  }

  public function process($data) {

    //Validate first
    $result = $this->validate($data);
    if (!$result->valid()) {
      return new PaymentGateway_Result(PaymentGateway_Result::FAILURE, false, $result->message());
    }

    //Mimic failures, like a gateway response such as 404, 500 etc.
    $amount = $data['Amount'];
    $cents = round($amount - intval($amount), 2);

    //TODO set this->gatewayResponse so that can be used by paymentProcessor to update payment
    
    switch ($cents) {
      case 0.01:
        return new PaymentGateway_Result(PaymentGateway_Result::FAILURE, false, 'Cents value was .01');
        break;
      default:
        return new PaymentGateway_Result(PaymentGateway_Result::SUCCESS);
    }
  }

  public function getResponse($response) {
    switch($response->getBody()) {
      case PaymentGateway_Result::SUCCESS:
        return new PaymentGateway_Result();
        break;
      case PaymentGateway_Result::FAILURE:
        return new PaymentGateway_Result(false);
        break;
      case PaymentGateway_Result::INCOMPLETE:
        $result = new PaymentGateway_Result();
        $result->setStatus(PaymentGateway_Result::INCOMPLETE);
        return $result;
        break;
      default:
        return new PaymentGateway_Result(false);
        break;
    }
  }
}

/**
 * Test class for gateway-hosted payment
 */
class DummyGatewayHostedGateway extends PaymentGateway {
  
  public function __construct() {
    parent::__construct();
    
    $this->gatewayURL = Director::baseURL() . 'dummy/external/pay';
  }
  
  /**
   * Override to cancel validation
   * 
   * @see PaymentGateway::validate()
   * 
   * @param Array $data
   * @return ValidationResult
   */
  public function validate($data) {
    $result = $this->getValidationResult();
    return $result;
  }

  public function process($data) {
    $postData = array(
      'Amount' => $data['Amount'],
      'Currency' => $data['Currency'],
      'ReturnURL' => $this->returnURL    
    ); 
    
    $queryString = http_build_query($postData);
    Controller::curr()->redirect($this->gatewayURL . '?' . $queryString);
  }

  public function getResponse($response) {
    $result = $response->getVar('result');
    switch ($result) {
      case PaymentGateway_Result::SUCCESS:
        return new PaymentGateway_Result();
        break;
      case PaymentGateway_Result::FAILURE:
        return new PaymentGateway_Result(false);
        break;
      case PaymentGateway_Result::INCOMPLETE:
        $result = new PaymentGateway_Result();
        $result->setStatus(PaymentGateway_Result::INCOMPLETE);
        return $result;
      default:
        return new PaymentGateway_Result(false);
        break;
    }
  }
}

/**
 * Imaginary place to visit external gateway
 */
class DummyExternalGateway_Controller extends Controller{

  function pay($request) {

    //Why do we set these in session?
    // Session::set('Amount', $request->getVar('Amount'));
    // Session::set('Currency', $request->getVar('Currenct'));

    //TODO this should be getter and setter for consistency of Session var etc.
    Session::set('ReturnURL', $request->getVar('ReturnURL'));
    
    return $this->customise(array(
      'Content' => "<h1>Fill out this form to make payment</h1>",
      'Form' => $this->PayForm()
    ))->renderWith('Page');
  }

  function PayForm() {

    $request = $this->getRequest();

    $fields = new FieldList(
      new TextField('Amount', 'Amount', $request->getVar('Amount')),
      new TextField('Currency', 'Currency', $request->getVar('Currency')),
      new TextField('CardHolderName', 'Card Holder Name', 'Test Testoferson'),
      new CreditCardField('CardNumber', 'Card Number', '1234567812345678'),
      new TextField('DateExpiry', 'Expiration date', '12/15')
    );

    $actions = new FieldList(
      new FormAction("dopay", 'Process Payment')
    );

    //TODO validate this form

    return new Form($this, "PayForm", $fields, $actions);
  }

  function dopay($data, $form) {

    $amount = $data['Amount'];
    $cents = round($amount - intval($amount), 2);

    switch ($cents) {
      case 0.01:
        $result = PaymentGateway_Result::FAILURE;
        break;
      case 0.02:
        $result = PaymentGateway_Result::INCOMPLETE;
        break;
      default:
        $result = PaymentGateway_Result::SUCCESS;
        break;
    }

    if ($returnURL = Session::get('ReturnURL')) {
      Controller::redirect($returnURL . "?result=$result");
    } 
    else {
      // TODO: Return a error for processor to handle rather than user_error
      user_error("Return URL is not set for this transaction", E_USER_ERROR);
    }
  }
}