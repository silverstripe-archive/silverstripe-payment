SilverStripe Payment Test Module
================================

Maintainer Contacts
-------------------
*  Ryan Dao
*  Frank Mullenger
*  Jeremy Shipman

Requirements
------------
* SilverStripe 3.0

Documentation
-------------

Installation Instructions
-------------------------
1. Place this directory in the root of your SilverStripe installation and call it 'payment'.
2. Visit yoursite.com/dev/build to rebuild the database.
3. Enable supported payment methods in your application yaml file

e.g: mysite/_config/Mysite.yaml
PaymentGateway:
  environment:
    'dev'

PaymentProcessor:
  supported_methods:
    dev:
      - 'DummyMerchantHosted'
    live:
      - 'DummyMerchantHosted'
      - 'DummyGatewayHosted'

Usage Overview
--------------