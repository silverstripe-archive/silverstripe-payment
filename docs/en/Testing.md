# Testing Guide

## Sandbox Testing
----------------------
Sandbox testing refers to UI testing for a particular gateway using its remote test server. Most payment gateways support test servers, we call them sandbox servers, for testing purposes. When testing under Sandbox mode, make sure the environment is set to 'dev'.

    PaymentGateway:
      environment: 'dev'
      
When implementing a new payment gateway, the developer should specify the sandbox testing information (if supported by the gateway) if he wishes to use sandbox testing. For example in PayPal payment: 

    PaymentFactory:
      PayPalDirect: 
        gateway_classes: 
          dev: PayPalDirectGateway
        
## Unit Testing
----------------------
Unit testing the payment gateways can done by using the mock gateway approach. Mock gateways are the gateway classes written by developers for testing purposes. Using mock gateways, payment data will not be sent to the remote servers, instead, the responses are mocked up and returned to the API for processing. 

There's no universal approach for creating a mock gateway because each gateway has a different response method and template. However, generally a mock gateway can be created by following these steps:

* Create a mock gateway class and specify it in the yaml config:
     
        PaymentFactory:
          YourPaymentGateay:
            gateway_classes:
              test: YourMockGateway
              
* Create response templates and methods to generate dummy responses. Different gateways have different response types (JSON, XML, NVP…) and templates, so it's up to the developer to implement. An example for NVP response is in the [PayPal module](https://github.com/ryandao/silverstripe-payment-paypal/blob/master/code/PayPalDirect.php#L58).
* Have the mock gateway return the response to the gateway API.

Please refer to the PayPal module for an example of a mock gateway and how to do unit testing on the mock gateway.