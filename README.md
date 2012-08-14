# SilverStripe Payment Module


## Maintainer Contacts
---------------------
*  Ryan Dao
*  Frank Mullenger
*  Jeremy Shipman

## Requirements
---------------------
* SilverStripe 3.0

## Documentation
---------------------
### Usage Overview
This module provides the base API for various payment methods 

### Installation Instructions

1. Place this directory in the root of your SilverStripe installation and call it 'payment'.
2. Visit yoursite.com/dev/build to rebuild the database.
3. Set the environment (optional). If not set, the default value is set to SilverStripe environment.

        PaymentGateway:
          'environment':
            'dev'
   
            
4. Enable supported payment methods in your application yaml file. Make sure that the respective sub-modules are installed. Only Dummy payment methods are shipped with the module.

        PaymentProcessor:
          supported_methods:
            'dev':
              - 'DummyMerchantHosted'
              - 'DummyGatewayHosted'
            'live':
              - 'PayPalDirect'
              - 'PayPalExpress'

### Development Guide

Developers can extend this module by adding more payment gateways. There are two types of payment gateway:

  * **Merchant-hosted gateway**: Buyers enter credit card and billing information on the site and all the data is posted to the external gateway server.
  * **Gateway-hosted gateway**: Buyers are redirected to the external gateway to complete the payment. The external gateway then redirects back to the site with the information about the payment status.

Each gateway should be implemented in a separate module. We call it gateway sub-module. Each gateway sub-module should have the following classes:
  
  * **Model extends Payment** (optional): The payment model. The default model is set to Payment if no model is implemented in the sub-module.
  * **Processor extends PaymentProcessor_MerchantHosted or PaymentProcessor_GatewayHosted** (optional): A processor is a SilverStripe Controller that acts as a bridge between the SilverStripe site and the external gateways. Most of the time there's no need to extend the base processor classes, but developers can still write their own if neccessary.
  * **Gateway extends PaymentGateway_MerchantHosted or PaymentGateway_GatewayHosted** (compulsary): The gateway-specific implementation. This is where most development will take place.

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