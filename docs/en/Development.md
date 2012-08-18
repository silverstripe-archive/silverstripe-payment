# Development Guide

## User-facing Development
--------------------------
First, construct the PaymentProcessor instance for the method you want to use:

    $processor = PaymentFactory::factory(<MethodName>)

Get the form fields for the method:
    
    $formFields = $processor->getFormFields()

Process the payment after form submission:
    
    try {
      // Set the url to redirect to after the payment is completed, e.g.
      $paymentProcessor->setRedirectURL($this->link() . 'completed');
      
      // Process the payment 
      $processor->capture($data)
    } catch(Exception e) {
      // Most likely due to connection cannot be extablished or validation fails
    }
    
If nothing goes wrong, the user will be redirected to the url set previously. In the controller for the url, the payment data can be retrieved with:

    $paymentID = Session::get('PaymentID');
    $payment = Payment::get()->byID($paymentID);
    
    // Do your stuff with the data…
    
## Gateway Development (Contribution Guide)
Developers can extend this module by adding more payment gateways. There are two types of payment gateway:

  * **Merchant-hosted gateway**: Buyers enter credit card and billing information on the site and all the data is posted to the external gateway server.
  * **Gateway-hosted gateway**: Buyers are redirected to the external gateway to complete the payment. The external gateway then redirects back to the site with the information about the payment status.

Each gateway should be implemented in a separate module. We call it gateway sub-module. Each gateway sub-module should have the following classes:
  
  * **Model extends Payment** (optional): The payment model. The default model is set to Payment if no model is implemented in the sub-module.
  * **Processor extends PaymentProcessor_MerchantHosted or PaymentProcessor_GatewayHosted** (optional): A processor is a SilverStripe Controller that acts as a bridge between the SilverStripe site and the external gateways. Most of the time there's no need to extend the base processor classes, but developers can still write their own if neccessary.
  * **Gateway extends PaymentGateway** (compulsary): The gateway-specific implementation. This is where most development will take place in.

Declare the gateway in the factory:

    GatewayName.yaml 
    ~~~~~
      PaymentFactory:
        GatewayName: 
          title:  // which will be show to the user
          model: ModelClass // optional  
          processor: ProcessorClass  // optional
          gateway_classes: 
            live: GatewayClass_Live
            dev: GatewayClass_Dev
            
Only when the gateway is declare in the factory yaml can we call PaymentFactory::factory('<GatewayName>') to construct the respective processor object.

Please refer to the [architecture document](https://github.com/ryandao/silverstripe-payment/tree/1.0/docs/Architecture.md) for better understanding of how the module works. The section about PaymentGateway covers pretty much all that is needed to develop a new payment method.