# Changelog

## 0.4.0

 * Security: Information Leak in DPSAdapter (see 0.3.1)

## 0.3.1

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