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

Find and install a payment method module, some that are currently available:  
	- [Cheque](https://github.com/frankmullenger/silverstripe-payment-cheque)
	- [PayPal](https://github.com/frankmullenger/silverstripe-payment-paypal)
	- [Paystation](https://github.com/frankmullenger/silverstripe-payment-paystation)
	- [Payment Express](https://github.com/frankmullenger/silverstripe-payment-paymentexpress)
	- [Secure Pay Tech](https://github.com/frankmullenger/silverstripe-payment-securepaytech)

Enable the payment method in the YAML configuration file, payment method names can be found in each payment method module YAML configuration file as the first "node" under "PaymentFactory:".  
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

Configure the payment method in the YAML configuration file if necessary, each payment method has slightly different requirements as far as configuration  
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

## License
Copyright (c) 2007-2013, SilverStripe Limited - www.silverstripe.com
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

* Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
* Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the 
	documentation and/or other materials provided with the distribution.
* Neither the name of SilverStripe nor the names of its contributors may be used to endorse or promote products derived from this software 
	without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE 
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE 
LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE 
GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, 
STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY 
OF SUCH DAMAGE.

