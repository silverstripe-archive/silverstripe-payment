<?php

/**
 * The unit testing for DPS payment
 * The testings cannot be run through without defining four Constants:
 * DPSPAY_USERID_TEST, DPSPAY_KEY_TEST, DPSPAY_USERID_TEST, DPSPAY_KEY_TEST
 * The testings cannot be run through without setting the above four constants to DPSAdapter correctly.
 */

class DPSPaymentTest extends SapphireTest implements TestOnly{
	
	static $right_cc_data = array(
		'CardHolderName' => 'SilverStripe Tester',
		'CardNumber' => array(
			'4111','1111','1111','1111'
		),
		'Cvc2' => '123',
	);
	
	static $wrong_cc_data = array(
		'CardHolderName' => 'SilverStripe Tester',
		'CardNumber' => array(
			'9999','9900','0000','0154'
			//'1234','5678','9012','3456'
		),
		'Cvc2' => '123',
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
		$data['CardNumber'] = array(
			'9999','9900','0000','0402'
		);
		return $data;
	}

	function setUp() {
		parent::setUp();

		$this->checkCredentials();
		$this->checkConnection();
	}
	
	function checkCredentials() {
		$defined = get_defined_constants();
		$consts = array(
			'DPSPOST_USERNAME_TEST' => 'get_pxpost_username', 
			'DPSPOST_PASSWORD_TEST' => 'get_pxpost_password',
			'DPSPAY_USERID_TEST' => 'get_pxpay_userid',
			'DPSPAY_KEY_TEST' => 'get_pxpay_key'
		);
		foreach($consts as $const => $method) {
			if(!defined($const)) {
				$this->markTestSkipped(sprintf(
					"Requires definition of the %s constant",
					$const
				));
			}
			if(DPSAdapter::$method() !== $defined[$const]) {
				$this->markTestSkipped(sprintf(
					'Current setting does not match test constant setting: DPSAdapter::%s()=%s, %s=%s',
					$method,
					DPSAdapter::$method(),
					$const,
					$defined[$const]
				));
			}
		}
	}

	function checkConnection() {
		$adapter = new DPSAdapter();
		$connected = $adapter->postConnect();
		if(!$connected) {
			$this->markTestSkipped("Can't run test without successfully test the connection to dps getway");
		}
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
	
	function testAuthSuccess(){
		$payment = $this->createAPayment('NZD', '1.00');
		$payment->auth(self::get_right_cc_data());

		$this->assertEquals($payment->TxnType, 'Auth');
		$this->assertEquals($payment->Status, 'Success');
	}
	
	function testAuthFailure(){
		$payment = $this->createAPayment('NZD', '1.00');
		$payment->auth(self::get_wrong_cc_data());

		sleep(5);

		$this->assertEquals($payment->Status, 'Failure');
		$this->assertContains('declined', $payment->Message);
	
		$payment->auth(self::get_expired_cc_data());

		$this->assertEquals($payment->Status, 'Failure');
		$this->assertContains('Card Expired', $payment->Message);
	}
	
	function testCompleteSuccess() {
		$auth = $this->createAPayment('NZD', '1.00');
		$auth->auth(self::get_right_cc_data());

		sleep(5);

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

		sleep(5);

		$complete = $this->createAPayment('USD', '100.00');
		$complete->AuthPaymentID = $auth->ID;
		$complete->write();
		$complete->complete();

		$this->assertEquals($complete->Status, 'Failure');
		$this->assertContains('The transaction was Declined', $complete->Message);
	}
	
	function testCompleteOnlyOnce() {
		$auth = $this->createAPayment('NZD', '1.00');
		$auth->auth(self::get_right_cc_data());

		sleep(5);

		$complete1 = $this->createAPayment('USD', '100.00');
		$complete1->AuthPaymentID = $auth->ID;
		$complete1->write();
		$complete1->complete();
		$this->assertEquals($complete1->Status, 'Success');

		sleep(5);

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
		$origUser = DPSAdapter::get_pxpost_username();
		$origPass = DPSAdapter::get_pxpost_password();

		$purchase = $this->createAPayment('NZD', '1.00');
	
		$purchase->purchase(self::get_wrong_cc_data());
		$this->assertEquals($purchase->Status, 'Failure');
		$this->assertContains('declined', $purchase->Message);
	
		$purchase->purchase(self::get_expired_cc_data());
		$this->assertEquals($purchase->Status, 'Failure');
		$this->assertContains('Card Expired', $purchase->Message);
	
		DPSAdapter::set_pxpost_account(DPSAdapter::get_pxpost_username(), 'wrongpass');
		$purchase->purchase(self::get_right_cc_data());
		$this->assertEquals($purchase->Status, 'Failure');
		$this->assertContains('The transaction was Declined', $purchase->Message);
		DPSAdapter::set_pxpost_account($origUser, $origPass);
	}
	
	function testRefundSuccess() {
		$auth = $this->createAPayment('NZD', '1.00');
		$auth->auth(self::get_right_cc_data());

		sleep(5);

		$complete = $this->createAPayment('USD', '100.00');
		$complete->AuthPaymentID = $auth->ID;
		$complete->write();
		$complete->complete();

		sleep(5);

		$refund1 = $this->createAPayment('USD', '100.00');
		$refund1->RefundedForID = $complete->ID;
		$refund1->write();
		$refund1->refund();
	
		$this->assertEquals($refund1->TxnType, 'Refund');
		$this->assertEquals($refund1->Status, 'Success');

		sleep(5);
	
		$purchase = $this->createAPayment('NZD', '100.00');
		$purchase->purchase(self::get_right_cc_data());

		sleep(5);
	
		$refund2 = $this->createAPayment('USD', '100.00');
		$refund2->RefundedForID = $purchase->ID;
		$refund2->write();
		$refund2->refund();

		$this->assertEquals($refund2->TxnType, 'Refund');
		$this->assertEquals($refund2->Status, 'Success');
		$this->assertEquals($refund2->Amount->Amount, 100.00);
		$this->assertEquals($refund2->Amount->Currency, 'NZD');
	}
	
	function testRefundFailure() {
		$purchase1 = $this->createAPayment('NZD', '100.00');
		$purchase1->purchase(self::get_expired_cc_data());

		sleep(5);

		$refund1 = $this->createAPayment('NZD', '100.00');
		$refund1->RefundedForID = $purchase1->ID;
		$refund1->write();
		$refund1->refund();

		$this->assertEquals($refund1->Status, 'Failure');
		$this->assertContains('The transaction was Declined', $refund1->Message);

		$purchase2 = $this->createAPayment('NZD', '100.00');
		$purchase2->purchase(self::get_right_cc_data());

		sleep(5);

		$refund2 = $this->createAPayment('NZD', '1000.00');
		$refund2->RefundedForID = $purchase2->ID;
		$refund2->write();
		$refund2->refund();

		$this->assertEquals($refund2->Status, 'Failure');
		$this->assertContains('The transaction was Declined', $refund2->Message);
	}
	
	function testMultipleRefund() {
		$purchase = $this->createAPayment('NZD', '2.00');
		$purchase->purchase(self::get_right_cc_data());

		sleep(5); // artificial delay to confirm the payment with DPS

		for($i = 0; $i < 2; $i++) {
			$refund = $this->createAPayment('NZD', '1.00');
			$refund->RefundedForID = $purchase->ID;
			$refund->write();
			$refund->refund();
			$this->assertEquals($refund->Status, 'Success');

			sleep(5); // artifical delay
		}

		$refund = $this->createAPayment('NZD', '1.00');
		$refund->RefundedForID = $purchase->ID;
		$refund->write();
		$refund->refund();
		$this->assertEquals($refund->Status, 'Failure');
		$this->assertContains('The transaction has already been fully refunded', $refund->Message);
	}

	function testRecurringDPSPayment() {
		$payment = $this->createARecurringPayment('NZD', '100.00');
		$payment->Frequency = 'Monthly';
		$payment->StartingDate = date('Y-m-d');
		$payment->Times = 100;
		$payment->write();
		$payment->merchantRecurringAuth(self::get_right_cc_data());
		$this->assertEquals($payment->Status, 'Success');
		$this->assertContains('Transaction Approved', $payment->Message);
	
		$date = $payment->StartingDate;
		for($i = 0; $i < 3; $i++) {
			$payment->payNext();

			$next = $payment->getLatestPayment();
			$this->assertEquals($next->Status, 'Success');
			$this->assertContains('Transaction Approved', $next->Message);
			$this->assertEquals($next->PaymentDate, $date);
		
			$date = date('Y-m-d', strtotime("1 month", strtotime($date)));
		}
	}
	
	function testDPShostedPurchase(){
		$purchase = $this->createAPayment('NZD', '100.00');
		DPSAdapter::set_mode('Unit_Test_Only');
		$url = $purchase->dpshostedPurchase(array());
		DPSAdapter::set_mode('Normal');
		$this->assertContains("https://sec.paymentexpress.com/pxpay/pxpay.aspx?userid=", $url);
	}

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
		$this->assertType('DataObjectSet', $payment->SuccessPayments());
		$this->assertEquals($payment->Payments()->count(), 3);
		$this->assertEquals($payment->SuccessPayments()->count(), 3);
	}
}
