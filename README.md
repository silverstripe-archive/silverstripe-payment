# Payment Module

## Overview 

Generic API for various payment gateways. 
Records payments and their status in the database. 

## Supported Gateways and Payment Methods

 * [Payment Express](http://paymentexpress.com) (Merchant hosted) - Supports Auth, Complete, Purchase, Refund, Validate
 * [Payment Express]((http://paymentexpress.com)) (DPS hosted) - Supports Auth, Purchase
 * [Eway](http://www.eway.com.au/)
 * [PayPal](http://www.paypal.com)
 * [PayStation](http://www.paystation.com)
 * [Worldpay](http://www.worldpay.com)
 * Cheque Payment (manual processing)

## Configuration

A project using this module needs to set its PDS account in project _config.php file,
-	If using DPS-hosted payment gateway (pxpost), set PXPost account:
	DPSAdapter::set_pxpost_account($your_pxpost_username, $your_pxpost_password);
-	If using Merchant-hosted payment geteway (pxpay), set PXPay account:
	DPSAdapter::set_pxpay_account($your_pxpay_userid, $your_pxpay_key);
-	If using both gateways, you need to set both above. This is very likely when using
	DPS-hosted to Auth a Credit Card and using Merchant-hosted to recursively pay.

This module is a stand-alone module, it is only dependent on SilverStripe core.
We have re-factored DPSPayment and make DPSPayment and previous DPSHostedPayment into one
payment object, the only difference between the two is they call different functions when 
making a transaction.

One of its common applications is to be used in E-commerce module. But in general,
it should be hook-up with any data object as long as this data object is payable,
such as a downloadable mp3, a E-book, booking a ticket on-line, donation, etc.
DPSPayment has been re-implemented in this way, though we need to check all other payment
methods in future releases.

## Troubleshooting

### Curl and CA Certificates on Windows

Some gateways (like `DPSPayment`) use PHP's curl in order to submit data,
usually through a secure SSL connection. In some cases, the CA certificates
aren't accepted. On Windows, PHP's curl doesn't come with any root CAs installed.
You'll need to add them manually. The easiest way is a global installation
through changing `php.ini`:

 * Download [http://curl.haxx.se/ca/cacert.pem](http://curl.haxx.se/ca/cacert.pem)
 * Move into a common directory, e.g. `c:\php`
 * Configure the setting in your `php.ini`: `curl.cainfo=c:\php\cacert.pem`