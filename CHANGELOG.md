# Changelog

## 0.4.1 - 2013-02-28

 * Security: Payment Information Leak in Test Harness Controller (see 0.3.2)

## 0.3.2 - 2013-02-28

 * Security: DPS Payment Information Leak in Test Harness Controller

Since 2010, the payment module included a "test harness" controller
([commit](https://github.com/silverstripe-labs/silverstripe-payment/commit/8f27918294ac34b688f137e36b424616df55dd7f),
which was not correctly secured against public access.
It allowed a broad range of operations against the configured DPS API,
including listing payments incl. amounts and transaction details,
refunding and authenticate existing payments,  create new payments.
It does not expose the actual payment API credentials, customer or credit card details.
The vulnerability also doesn't allow directing payments to a different account.

This affects all recent versions of the module, but is limited to the
DPS/PaymentExpress payment provider.

We have removed the functionality from the module. If you are using
the functionality, please port it into your own codebase and ensure
the controller is secured to ADMIN permissions.
As a hotfix, you can also remove code/Harness.php to secure the installation.
In this case, don't forget to flush the manifest cache by appending ?flush=1 to any SilverStripe URL.

Reporter: Nicolaas Thiemen-Francken

## 0.4.0 - 2013-02-20

 * Security: Information Leak in DPSAdapter (see 0.3.1)

## 0.3.1 - 2013-02-20

 * Security: Information Leak in DPSAdapter

Severity: Important

Description: Exposure of DPS credentials through web URLs, routed through the `DPSAdapter` controller.

Impact: An attacker might be able to simulate payments using live payment gateway credentials.
With knowledge of the DPS transaction number, he could also operate on existing payments.
In case credentials are reused for other logins, these might get compromised as well.
The DPS [PXPost](http://www.paymentexpress.com/Technical_Resources/Ecommerce_NonHosted/PxPost) 
and [PXPay](http://www.paymentexpress.com/Technical_Resources/Ecommerce_Hosted/PxPay) 
APIs don't expose customer data and truncates credit card data, so the impact is limited.

## 0.3.0

## 0.2

## 0.1