<?php

class PayPal_Payment extends Payment {
  
}

class PayPal_Gateway extends Payment_Gateway {
  
  protected $gatewayURL;
  
  /**
   * The PayPal method in use. 
   * 
   * @var String
   */
  protected $method;
  
  public function process($data) {
    
  }
  
  /**
   * Tell PayPal to proceed an action
   * 
   * @param String $operation
   */
  public function operate($action) {
    
  }
}

class PayPal_Gateway_Direct extends PayPal_Gateway {
  
}

class PayPal_Gateway_Express extends PayPal_Gateway {
  
}

