<?php

Director::addRules(50, array(
	WorldpayPayment_Handler::$URLSegment . '/$Action/$ID' => 'WorldpayPayment_Handler',
	PayPalPayment_Handler::$URLSegment . '/$Action/$ID' => 'PayPalPayment_Handler',
	'harness/$Action/$Class/$ID' => 'DPSHarness', 
));

?>