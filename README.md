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