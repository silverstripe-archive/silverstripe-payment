# SilverStripe Payment Module

## Maintainer Contacts
*  [Ryan Dao](https://github.com/ryandao)
*  [Frank Mullenger](https://github.com/frankmullenger)
*  [Jeremy Shipman](https://github.com/jedateach)

## Requirements
* SilverStripe 3.*

## Documentation

### Usage Overview
This module provides the base API for various payment methods. This module is usually used in conjunction with other payment modules that integrate with particular payment gateways.

### Installation 
1. Place this directory in the root of your SilverStripe installation and call it 'payment'.

2. Visit yoursite.com/dev/build?flush=1 to rebuild the database.

3. Configure the payment module by creating a YAML configuration file  
e.g: mysite/_config/Mysite.yaml

```yaml
PaymentGateway:
  environment:
    'dev'

PaymentProcessor:
  supported_methods:
    dev:
      - 'Cheque'
    live:
      - 'Cheque'
```

**Note**  
The above configuration sets the payment into dev mode and assumes that the "payment-cheque" module is installed (see "Installing Payment Methods" below).  
YAML configuration files need to use spaces instead of tabs.  
You need to run a /dev/build?flush=1 each time the YAML configuration file is changed.  
							
### Testing 
After installing this module you can access unit tests at yoursite.com/dev/tests.

Alternatively install the [payment test module](https://github.com/frankmullenger/silverstripe-gsoc-payment-test) for manual integration testing with different supported payment gateways. Dummy payment methods are included with the payment test module, it is also good to test with the "payment-cheque" module as this is a very simple way of processing payments.

### Installing Payment Methods
Other payment methods such as cheque, PayPal, PaymentExpress, Paystation etc. can be installed as seperate modules and enabled in the YAML configuration file. 

1. Find and install a payment method module, some that are currently available:
	- [Cheque](https://github.com/frankmullenger/silverstripe-payment-cheque)
	- [PayPal](https://github.com/frankmullenger/silverstripe-payment-paypal)
	- [Paystation](https://github.com/frankmullenger/silverstripe-payment-paystation)
	- [Payment Express](https://github.com/frankmullenger/silverstripe-payment-paymentexpress)
	- [Secure Pay Tech](https://github.com/frankmullenger/silverstripe-payment-securepaytech)

2. Enable the payment method in the YAML configuration file, payment method names can be found in each payment method module YAML configuration file as the first "node" under "PaymentFactory:".  
e.g: mysite/_config/Mysite.yaml

```yaml
PaymentGateway:
  environment:
    'dev'

PaymentProcessor:
  supported_methods:
    dev:
      - 'Cheque'
      - 'DummyMerchantHosted'
      - 'DummyGatewayHosted'
      - 'PayPalExpress'
      - 'PaymentExpressPxPay'
      - 'PaystationThreeParty'
      - 'SecurePayTech'
    live:
      - 'Cheque'
```

3. Configure the payment method in the YAML configuration file if necessary, each payment method has slightly different requirements as far as configuration  
e.g mysite/_config/Mysite.yaml

```yaml
PayPalGateway_Express: 
  live:
    authentication:
      username: ''
      password: ''
      signature: ''
  dev:
    authentication:
      username: ''
      password: ''
      signature: ''
```



