<?php

class RecurringPayment extends DataObject{
	static $db = array(
		'Status' => "Enum('Incomplete,Success,Failure,Pending','Incomplete')",
		'Amount' => 'Money',
		'Message' => 'Text',
		'IP' => 'Varchar',
		'ProxyIP' => 'Varchar',
		'PaidForID' => "Int",
		'PaidForClass' => 'Varchar',
		
		//The following fields store the recurring payment schedulling info
		'Frequency' => "Enum('Weekly,Monthly,Yearly','Monthly')",
		'StartingDate' => "Date",
		'Times' => "Int",

		//Usered for store any Exception during this payment Process.
		'ExceptionError' => 'Text'
	);
	
	static $has_many = array(
		'Payments' => "Payment"
	);
	
	function populateDefaults() {
		parent::populateDefaults();
		
		$this->Amount->Currency = Payment::site_currency();
		$this->setClientIP();
 	}
	
	/**
	 * Set the IP address of the user to this payment record.
	 * This isn't perfect - IP addresses can be hidden fairly easily.
	 */
	function setClientIP() {
		$proxy = null;
		$ip = null;
		
		if(isset($_SERVER['HTTP_CLIENT_IP'])) $ip = $_SERVER['HTTP_CLIENT_IP'];
		elseif(isset($_SERVER['REMOTE_ADDR'])) $ip = $_SERVER['REMOTE_ADDR'];
		else $ip = null;
		
		if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$proxy = $ip;
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		
		// Only set the IP and ProxyIP if none currently set
		if(!$this->IP) $this->IP = $ip;
		if(!$this->ProxyIP) $this->ProxyIP = $proxy;
	}
	
	function CanPayNext(){
		$success = $this->Status = 'Success';
		if($this->Times && $successPayments = $this->SuccessPayments()){			
			return $success && $this->Times > $successPayments->count();
		}else{
			return $success;
		}
	}
	
	function SuccessPayments(){
		return DataObject::get("Payment", "\"RecurringPaymentID\" = '".$this->ID."' AND \"Status\" = 'Success'", "\"PaymentDate\" DESC");
	}
	
	function getNextPayment(){
		if($latest = $this->getLatestPayment()){
			$next = $this->generateNextPaymentFrom($latest);
		}else{
			$next = $this->generateFirstPayment();
		}
		DB::getConn()->transactionSavepoint("NextPaymentGot");
		return $next;
	}
	
	function getLatestPayment($successonly = true){
		if($successonly){
			return DataObject::get_one("Payment", "\"RecurringPaymentID\" = '".$this->ID."' AND \"Status\" = 'Success'", false, "\"PaymentDate\" DESC");
		}else{
			return DataObject::get_one("Payment", "\"RecurringPaymentID\" = '".$this->ID."'", false, "\"PaymentDate\" DESC, \"LastEdited\" DESC");
		}
	}
	
	function payNext(){
		DB::getConn()->startTransaction();
		try{
			if($next = $this->getNextPayment()){
				$next->payAsRecurring();
			}
			DB::getConn()->endTransaction();
		}catch(Exception $e){
			DB::getConn()->transactionRollback('NextPaymentGot');
			DB::getConn()->endTransaction();
			$latestPayment = $this->getLatestPayment($successonly = false);
			$latestPayment->handleError($e);
		}
	}
	
	function generateFirstPayment(){
		$payment = new Payment();
		$payment->RecurringPaymentID = $this->ID;
		$payment->PaymentDate = $this->StartingDate;
		$payment->Amount->Amount = $this->Amount->Amount;
		$payment->Amount->Currency = $this->Amount->Currency;
		$payment->write();
		return $payment;
	}
	
	function generateNextPaymentFrom($latest){
		switch ($this->Frequency){
			case 'Weekly':
				$date = date('Y-m-d', strtotime('+1 week', strtotime($latest->PaymentDate)));
				break;
			case 'Monthly':
				$date = date('Y-m-d', strtotime('+1 month', strtotime($latest->PaymentDate)));
				break;
			case 'Yearly':
				$date = date('Y-m-d', strtotime('+1 year', strtotime($latest->PaymentDate)));
				break;
		}
		
		$payment = new Payment();
		$payment->RecurringPaymentID = $this->ID;
		$payment->PaymentDate = $date;
		$payment->Amount->Amount = $this->Amount->Amount;
		$payment->Amount->Currency = $this->Amount->Currency;
		$payment->write();
		return $payment;
	}
	
	function handleError($e){
		$this->ExceptionError = $e->getMessage();
		$this->write();
	}
}

?>