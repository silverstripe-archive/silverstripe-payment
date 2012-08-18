# Architecture

## Basics to understand
-----------------------
The Payment moule provides an API for implementing various payment gateway integration with SilverStripe 3.0. The architecture consists of 3 main components:

* Payment: The DataObject model that stores payment data. This is the default model for all payments. 
* PaymentGateway: Default class for handling external gateway interactions. Each gateway submodule must have its own gateway implementation.
* PaymentProcessor: The controller that handles HTTP requests and responses as well as data returned back from external gateways. It acts as a bridge between Payment and PaymentGateway. 

![](https://photos-3.dropbox.com/thumb/AACB-PnUz8gxIPzgVxGyzI1__LhVAsArD3C1idGu8OsCtA/39287717/png/1024x768/2/1345284000/0/2/SilverStripePaymentArchitecture.png/xy72WbjCRxMaYK3uMbaUzUBMgDJ82mbdC4xdh6F6LIs)

## Payment
-----------------------
Core attributes:

* Status: Default to 'Pending'. When the payment is completed, it can be marked as either 'Success', 'Failure', or 'Incomplete'
* Amount: Amount and currency of the transaction
* Message: The message returned back from the external gateway. It could be a warning or confirmation message.
* ErrorCode: Stores the error code returned from the external gateway if something is wrong.
* ErrorMessage: The message associating with 'ErrorCode'
* HTTPStatus: Stores the HTTP status code of the transaction. It's always 200 if a connection with the external gateway can be established. 

## PaymentGateway
-----------------------
This is the most important component of the module. Each payment method (PayPal, DPS…) has its own PaymentGateway implementation based on the API provided by the gateway. The important methods are:

* process(): Do the actual processing of the payment data (sending requests to the external gateway and getting responses)
* validate(): Validate the passed payment data against gateway requirements. The default validation rules are included in the parent PaymentGateway class
* getValidationResult(): Since validation results are not saved in database, developers can use this method to get the Validation_Result object for a running gateway instance to display to the user.
* getRequest() (For gateway-hosted payment only): When the user finished the payment process at the external gateway, he will be redirected back to our website. This method parse the HTTP request of that redirection and attempts to retrieve the payment result from the external gateway.

## PaymentProcessor
-----------------------
PaymentProcessor does the following tasks:

* Provides payment forms fields.
* Retrieve payment results after PaymentGateway has finished processing the payment. Save the results into the Payment model.
* Handles redirections.  

Important methods:

* setup(): Save preliminary data (amount, currency…)
* capture(): Initiate the payment; forward the payment data to PaymentGateway; retrieve the result from PaymentGateway.
* complete() (For gateway-hosted payment only): Get the HTTP request when the external gateway redirects back. Retrieve the payment result from PaymentGateway
* doRedirect(): After the payment is completed, redirect to a SS page set by the developer. It's up to the developer to specify which url to redirect to as well as what to do with the payment data.

## Payment Flow Summary
-----------------------
In summary, the flow to process one payment is:

* Merchant-hosted: **SS page -> PaymentProcessor::capture() -> PaymentGateway::process() -> PaymentProcessor::doRedirect()**
* Gateway-hosted: **SS page -> PaymentProcessor::capture() -> PaymentGateway::process() -> External gateway -> PaymentProcessor::complete() -> PaymentGateway::getResponse() -> PaymentProcessor::doRedirect()**