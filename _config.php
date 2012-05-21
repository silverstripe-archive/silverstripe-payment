<?php

Director::addRules(50, array(
	WorldpayPayment_Handler::$URLSegment . '/$Action/$ID' => 'WorldpayPayment_Handler',
	PayPalPayment_Handler::$URLSegment . '/$Action/$ID' => 'PayPalPayment_Handler',
	PaystationHostedPayment_Handler::$URLSegment . '/$Action/$ID' => 'PaystationHostedPayment_Handler',
	'harness/$Action/$Class/$ID' => 'Harness', 
));

Object::add_extension('Member', 'PayerHavingReceipt');