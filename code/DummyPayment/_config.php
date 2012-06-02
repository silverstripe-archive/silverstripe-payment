<?php

Director::addRules(100, array(
   Dummy_Payment_Controller::$URLSegment . '/$Action/$ID' => 'Dummy_Payment_Controller',
));

Dummy_Payment_Controller::set_test_mode('cancel');
