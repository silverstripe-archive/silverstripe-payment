<?php

class DPSPaymentTest extends SapphireTest implements TestOnly{
	static $right_cc_data = array(
		'CardHolderName' => 'SilverStripe Tester',
		'CardNumber' => array(
			'4111','1111','1111','1111'
		),
		'Cvc2' => '1111',
	);
	static $wrong_cc_data = array(
		'CardHolderName' => 'SilverStripe Tester',
		'CardNumber' => array(
			'1234','5678','9012','3456'
		),
		'Cvc2' => '1111',
	);
	
	static function get_right_cc_data(){
		$data = self::$right_cc_data;
		$data['DateExpiry'] = date('my', strtotime('+1 year'));
		return $data;
	}
	static function get_wrong_cc_data(){
		$data = self::$wrong_cc_data;
		$data['DateExpiry'] = date('my', strtotime('+1 year'));
		return $data;
	}
	static function get_expired_cc_data(){
		$data = self::$right_cc_data;
		$data['DateExpiry'] = date('my', strtotime('-1 year'));
		return $data;
	}
	function setUp(){
		parent::setUp();
		if(!defined('DPSPOST_USERNAME')) define('DPSPOST_USERNAME', 'SilverStripedev');
		if(!defined('DPSPOST_PASSWORD')) define('DPSPOST_PASSWORD', 'not2Bor2B');
		if(!defined('DPSPAY_USERID')) define('DPSPAY_USERID', 'SilverStripe_dev');
		if(!defined('DPSPAY_KEY')) define('DPSPAY_KEY', 'a82e97f22840ea6c9c437c270b7ad974d857262d654f2923c41fcae6412c3d78');
	}
	
	function createAPayment($currency, $amount){
		$payment = new DPSPayment();
		$payment->Amount->Currency = $currency;
		$payment->Amount->Amount = $amount;
		$payment->write();
		return $payment;
	}
	
	function createARecurringPayment($currency, $amount){
		$payment = new DPSRecurringPayment();
		$payment->Amount->Currency = $currency;
		$payment->Amount->Amount = $amount;
		$payment->write();
		return $payment;
	}
	
	/*function testAuthSuccess(){
		$payment = $this->createAPayment('NZD', '1.00');
		
		$payment->auth(self::get_right_cc_data());
		$this->assertEquals($payment->TxnType, 'Auth');
		$this->assertEquals($payment->Status, 'Success');
	}
	
	function testAuthFailure(){
		$payment = $this->createAPayment('NZD', '1.00');
		
		$payment->auth(self::get_wrong_cc_data());
		$this->assertEquals($payment->Status, 'Failure');
		$this->assertContains('Invalid Card', $payment->Message);
		
		$payment->auth(self::get_expired_cc_data());
		$this->assertEquals($payment->Status, 'Failure');
		$this->assertContains('Card Expired', $payment->Message);
	}
	
	function testCompleteSuccess(){
		$auth = $this->createAPayment('NZD', '1.00');
		$auth->auth(self::get_right_cc_data());
		
		$complete = $this->createAPayment('USD', '100.00');
		$complete->AuthPaymentID = $auth->ID;
		$complete->write();
		$complete->complete();
		$this->assertEquals($complete->TxnType, 'Complete');
		$this->assertEquals($complete->Status, 'Success');
	}
	
	function testCompleteFailure(){
		$auth = $this->createAPayment('NZD', '1.00');
		$auth->auth(self::get_wrong_cc_data());
		
		$complete = $this->createAPayment('USD', '100.00');
		$complete->AuthPaymentID = $auth->ID;
		$complete->write();
		$complete->complete();
		$this->assertEquals($complete->Status, 'Failure');
		$this->assertContains('The transaction was Declined', $complete->Message);
	}
	
	function testCompleteOnlyOnce(){
		$auth = $this->createAPayment('NZD', '1.00');
		$auth->auth(self::get_right_cc_data());
		
		$complete1 = $this->createAPayment('USD', '100.00');
		$complete1->AuthPaymentID = $auth->ID;
		$complete1->write();
		$complete1->complete();
		$this->assertEquals($complete1->Status, 'Success');
		
		$complete2 = $this->createAPayment('USD', '100.00');
		$complete2->AuthPaymentID = $auth->ID;
		$complete2->write();
		$complete2->complete();
		$this->assertEquals($complete2->Status, 'Failure');
		$this->assertContains('This transaction has been previously completed and cannot be completed again.', $complete2->Message);
	}
	
	function testPurchaseSuccess(){
		$purchase = $this->createAPayment('NZD', '100.00');
		
		$purchase->purchase(self::get_right_cc_data());
		$this->assertEquals($purchase->TxnType, 'Purchase');
		$this->assertEquals($purchase->Status, 'Success');
	}
	
	function testPurchaseFailure(){
		$purchase = $this->createAPayment('NZD', '1.00');
		
		$purchase->purchase(self::get_wrong_cc_data());
		$this->assertEquals($purchase->Status, 'Failure');
		$this->assertContains('Invalid Card', $purchase->Message);
		
		$purchase->purchase(self::get_expired_cc_data());
		$this->assertEquals($purchase->Status, 'Failure');
		$this->assertContains('Card Expired', $purchase->Message);
		
		DPSAdapter::set_pxpost_account(DPSPOST_USERNAME, 'wrongpass');
		$purchase->purchase(self::get_right_cc_data());
		$this->assertEquals($purchase->Status, 'Failure');
		$this->assertContains('The transaction was Declined', $purchase->Message);
		DPSAdapter::set_pxpost_account(DPSPOST_USERNAME, DPSPOST_PASSWORD);
	}
	
	function testRefundSeccess(){
		$auth = $this->createAPayment('NZD', '1.00');
		$auth->auth(self::get_right_cc_data());
		
		$complete = $this->createAPayment('USD', '100.00');
		$complete->AuthPaymentID = $auth->ID;
		$complete->write();
		$complete->complete();
		
		$refund1 = $this->createAPayment('USD', '100.00');
		$refund1->RefundedForID = $complete->ID;
		$refund1->write();
		$refund1->refund();
		
		$this->assertEquals($refund1->TxnType, 'Refund');
		$this->assertEquals($refund1->Status, 'Success');
		
		$purchase = $this->createAPayment('NZD', '100.00');
		$purchase->purchase(self::get_right_cc_data());
		
		$refund2 = $this->createAPayment('USD', '100.00');
		$refund2->RefundedForID = $purchase->ID;
		$refund2->write();
		$refund2->refund();
		$this->assertEquals($refund2->TxnType, 'Refund');
		$this->assertEquals($refund2->Status, 'Success');
		$this->assertEquals($refund2->Amount->Amount, 100.00);
		$this->assertEquals($refund2->Amount->Currency, 'NZD');
	}
	
	function testRefundFailure(){
		$purchase1 = $this->createAPayment('NZD', '100.00');
		$purchase1->purchase(self::get_expired_cc_data());
		
		$refund1 = $this->createAPayment('NZD', '100.00');
		$refund1->RefundedForID = $purchase1->ID;
		$refund1->write();
		$refund1->refund();
		$this->assertEquals($refund1->Status, 'Failure');
		$this->assertContains('The transaction was Declined', $refund1->Message);
		
		$purchase2 = $this->createAPayment('NZD', '100.00');
		$purchase2->purchase(self::get_right_cc_data());
		$refund2 = $this->createAPayment('NZD', '1000.00');
		$refund2->RefundedForID = $purchase2->ID;
		$refund2->write();
		$refund2->refund();
		$this->assertEquals($refund2->Status, 'Failure');
		$this->assertContains('The transaction was Declined', $refund2->Message);
	}
	
	function testMultipleRefund(){
		$purchase = $this->createAPayment('NZD', '2.00');
		$purchase->purchase(self::get_right_cc_data());
		
		for($i=0;$i<2;$i++){
			$refund = $this->createAPayment('NZD', '1.00');
			$refund->RefundedForID = $purchase->ID;
			$refund->write();
			$refund->refund();
			$this->assertEquals($refund->Status, 'Success');
		}
		
		$refund = $this->createAPayment('NZD', '1.00');
		$refund->RefundedForID = $purchase->ID;
		$refund->write();
		$refund->refund();
		$this->assertEquals($refund->Status, 'Failure');
		$this->assertContains('The transaction has already been fully refunded', $refund->Message);
	}
	
	function testRecurringDPSPayment(){
		$payment = $this->createARecurringPayment('NZD', '100.00');
		$payment->Frequency = 'Monthly';
		$payment->StartingDate = date('Y-m-d');
		$payment->Times = 100;
		$payment->write();
		$payment->merchantRecurringAuth(self::get_right_cc_data());
		$this->assertEquals($payment->Status, 'Success');
		$this->assertContains('Transaction Approved', $payment->Message);
		
		$date = $payment->StartingDate;
		for($i=0;$i<3;$i++){
			$payment->payNext();

			$next = $payment->getLatestPayment();
			$this->assertEquals($next->Status, 'Success');
			$this->assertContains('Transaction Approved', $next->Message);
			$this->assertEquals($next->PaymentDate, $date);
			
			$date = date('Y-m-d', strtotime("1 month", strtotime($date)));
		}
	}
	
	/*function testDPShostedPurchase(){
		$purchase = $this->createAPayment('NZD', '100.00');
		DPSAdapter::set_mode('Unit_Test_Only');
		$url = $purchase->dpshostedPurchase(array());
		DPSAdapter::set_mode('Normal');
		$this->assertContains("https://www.paymentexpress.com/pxpay/pxpay.aspx?userid=", $url);
	}
	
	function testErrorHandling(){
		$purchase = $this->createAPayment('NZD', '100.00');
		DPSAdapter::set_mode('Error_Handling_Mode');
		$purchase->purchase(self::get_right_cc_data());
		DPSAdapter::set_mode('Normal');
		
		$purchase = DataObject::get_by_id('DPSPayment', $purchase->ID);
		$this->assertEquals('Incomplete', $purchase->Status);
		$this->assertContains("'tsixe_dluoc_reven_taht_noitcnuf_a_function_that_never_could_exist' does not exist", $purchase->ExceptionError);
	}*/
	
	function testDBTransaction(){
		$payment = $this->createARecurringPayment('NZD', '100.00');
		$payment->Frequency = 'Monthly';
		$payment->StartingDate = date('Y-m-d');
		$payment->Times = 100;
		$payment->write();
		$payment->merchantRecurringAuth(self::get_right_cc_data());
		
		if(defined('SS_DATABASE_CLASS') && SS_DATABASE_CLASS == 'PostgreSQLDatabase'){		
			DPSAdapter::set_mode('Rolling_Back_Mode');
			$payment->payNext();
			DPSAdapter::set_mode('Normal');
		}
		
		DPSAdapter::set_mode('Error_Handling_Mode');
		$payment->payNext();
		DPSAdapter::set_mode('Normal');
		
		for($i=0;$i<2;$i++){
			$payment->payNext();
		}

		$this->assertType('DataObjectSet', $payment->Payments());
		$this->assertEquals($payment->Payments()->count(), 3);
		$this->assertType('DataObjectSet', $payment->SuccessPayments());
		$this->assertEquals($payment->SuccessPayments()->count(), 2);
	}
}

?>