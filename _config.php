<?php

Director::addRules(50, array(
	WorldpayPayment_Handler::$URLSegment . '/$Action/$ID' => 'WorldpayPayment_Handler',
	PayPalPayment_Handler::$URLSegment . '/$Action/$ID' => 'PayPalPayment_Handler',
));

Object::add_extension('Member', 'PayerHavingReceipt');
?