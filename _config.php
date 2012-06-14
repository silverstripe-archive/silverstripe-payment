<?php

Director::addRules(50, array(  
  'test/order/$Action/$ID' => 'OrderDemoPage_Controller',
));

ini_set('display_errors', 1);
ini_set("log_errors", 1);
error_reporting(E_ALL);