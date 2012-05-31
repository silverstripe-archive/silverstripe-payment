<?php

Director::addRules(50, array(  
  'test/order/$Action/$ID' => 'OrderDemoPage_Controller',
  // TODO: Move this to submodule
  Dummy_Payment_Controller::$URLSegment . '/$Action/$ID' => 'Dummy_Payment_Controller',
));
