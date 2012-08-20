# SilverStripe Payment Module


## Maintainer Contacts
---------------------
*  [Ryan Dao](https://github.com/ryandao)
*  [Frank Mullenger](https://github.com/frankmullenger)
*  [Jeremy Shipman](https://github.com/jedateach)

## Requirements
---------------------
* SilverStripe 3.0

## Documentation
---------------------
### Usage Overview
This module provides the base API for various payment methods 

### Installation 

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
              
### Testing 
After cloning this GitHub repository, make sure all the tests in <yoursite>/dev/tests are passed to make sure the environemnt settings are correct.  

Install the [PaymentTest module](https://github.com/ryandao/silverstripe-gsoc-payment-test) to do UI testing for the supported payment gateways. By default, DummyMerchantHosted and DummyGatewayHosted are enabled for testing purposes. For other gateways, you must specify them under 'supported_methods' in the yaml config.

### Installing Payment methods
Payment methods are shipped separately. Each method is one module and can be installed in the same way as other SilverStripe modules.

List of current supported payment methods:  

- [PayPal](https://github.com/ryandao/silverstripe-payment-paypal)