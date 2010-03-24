<?php

class DPSHarness extends Page_Controller{
	function init(){
		parent::init();
		Requirements::css('payment/css/DPSHarness.css');
	}
	
	function show(){
		$content = $this->renderWith("DPSHarness");
		
		$controller = new DPSHarness();
		$controller->init();
		
		$customisedController = $controller->customise(array(
			"Content" => $content,
			"Title" => ''
		));
		
		return $customisedController->renderWith("Page");
	}
	
	function handleError($payment, $e){
		$payment->handleError($e);
	}
	
	function paynext(){
		$payment = DataObject::get_by_id($this->URLParams['Class'], $this->URLParams['ID']);
		DB::getConn()->startTransaction();
		try{
			$payment->paynext();
			DB::getConn()->endTransaction();
		}catch(Exception $e){
			DB::getConn()->transactionRollback('NextPaymentGot');
			DB::getConn()->endTransaction();
			$latestPayment = $payment->getLatestPayment($successonly = false);
			$this->handleError($latestPayment, $e);
		}
		Director::redirectBack();
	}
	
	function getTitle(){
		$title = "";
		if(isset($_GET['currentMethod']) && $_GET['currentMethod']){
			$title .= $_GET['currentMethod'];
		} 
		
		if(isset($_GET['currentForm']) && $_GET['currentForm']){
			$forms = singleton($_GET['currentMethod'])->getTestableForms();
			$title .= ": ".$forms[$_GET['currentForm']];
		}
		
		if(!$title) $title = "Test Harness";
		return $title;
	}
	
	
	function LeftContent(){
		// get all Payment subclasses
		$payments = array('DPSPayment', "DPSRecurringPayment");

		// $payment[0] is the Payment base class, so need to shift

		if(count($payments)) {
			$link = "<ul>";
			foreach($payments as $payment){
				$paymentlink = "<a href=\"".$this->Link()."?currentMethod=".$payment."\">".$payment."</a>";
				
				//$link .= $paymentlink;
				$forms = singleton($payment)->getTestableForms();
				if(count($forms)){
					$testformlink =  "<ul>".$payment;
					foreach($forms as $form => $title){
						$testformlink .= "<li><a href=\"".$this->Link()."?currentMethod=".$payment."&currentForm=".$form."\">"." - ".$title."</a></li>";
					}
					$testformlink .= "</ul>";
					$link .= "<li>".$testformlink."</li>";
				}else{
					$link .= "<li>".$paymentlink."</li>";
				}
			}
			$link .= "</ul>";
		}
		return $link;
	}
	
	function RightContent(){
		if($this->URLParams['ID'] && $this->URLParams['Class']) {
			$payment = DataObject::get_by_id($paymentClass =$this->URLParams['Class'], $this->URLParams['ID'] );
			$query = "";
			if(isset($_GET['currentMethod']) && $_GET['currentMethod'] && isset($_GET['currentForm']) && $_GET['currentForm']){
				$query = "?currentMethod=".$_GET['currentMethod']."&currentForm=".$_GET['currentForm'];
			}

			return $payment->renderWith($paymentClass);
		}
	}
	
	function MiddleContent(){
		$ret = '';
		if(isset($_GET['currentMethod']) && $_GET['currentMethod'] && isset($_GET['currentForm']) && $_GET['currentForm']){
			$className = $_GET['currentMethod'];
			$payment = singleton($className);
			$form = $payment->getForm($_GET['currentForm']);
			$form -> setFormAction($this->FormActionLink("HarnessForm"));
		}else{
			$message = "Click on a testable payment method that you want to test on";
			$form = new Form($this, 'HarnessForm', new FieldSet(), new FieldSet());
			$form->sessionMessage($message, 'bad');
		}
		return $form->forTemplate();
	}
	
	function HarnessForm(){
		if(isset($_GET['currentMethod']) && $_GET['currentMethod'] && isset($_GET['currentForm']) && $_GET['currentForm']){
			$className = $_GET['currentMethod'];
			$payment = singleton($className);
			$form = $payment->getForm($_GET['currentForm']);
			$form -> setFormAction($this->FormActionLink("HarnessForm"));
			return $form;
		}
	}
	
	function FormActionLink($formname){
		$formObjectLink = Controller::join_links($this->Link(), $formname);
		if(isset($_GET['currentMethod']) && $_GET['currentMethod'] && isset($_GET['currentForm']) && $_GET['currentForm']){
			$formObjectLink = Controller::join_links($formObjectLink, "?currentMethod=".$_GET['currentMethod']."&currentForm=".$_GET['currentForm']);
		}
		return $formObjectLink;
	}
	
	private function createPayment($data, $form, $request){
		$classname = $_GET['currentMethod'];
		$payment = new $classname();
		$form->saveInto($payment);
		$payment->write();
		return $payment;
	}
	
	private function showPayment($payment){
		if($payment->DPSHostedRedirectURL) {
			Director::redirect($payment->DPSHostedRedirectURL);
		}else{
			$query = "?currentMethod=".$_GET['currentMethod']."&currentForm=".$_GET['currentForm'];
			Director::redirect("harness/show/".$payment->ClassName."/".$payment->ID.$query);
		}
	}
	
	function doAuthPayment($data, $form, $request){
		if(isset($_GET['currentMethod']) && $_GET['currentMethod'] && isset($_GET['currentForm']) && $formname = $_GET['currentForm']) {
			$payment = $this->createPayment($data, $form, $request);
			DB::getConn()->startTransaction();
			try{
				$payment->TxnType = "Auth";
				$payment->write();
			
				$payment->auth($data);
				DB::getConn()->endTransaction();
			}catch(Exception $e){
				DB::getConn()->transactionRollback();
				$this->handleError($payment, $e);
			}
			$this->showPayment($payment);
		}
	}
	
	function doCompletePayment($data, $form, $request){
		if(isset($_GET['currentMethod']) && $_GET['currentMethod'] && isset($_GET['currentForm']) && $formname = $_GET['currentForm']) {
			$payment = $this->createPayment($data, $form, $request);
			DB::getConn()->startTransaction();
			try{
				$auth = $payment->AuthPayment();
				$payment->TxnType = "Complete";
				$payment->MerchantReference = "Complete: ".$auth->MerchantReference;
				$payment->write();
			
				$payment->complete($data);
				DB::getConn()->endTransaction();
			}catch(Exception $e){
				DB::getConn()->transactionRollback();
				$this->handleError($payment, $e);
			}
			$this->showPayment($payment);
		}
	}
	
	function doPurchasePayment($data, $form, $request){
		if(isset($_GET['currentMethod']) && $_GET['currentMethod'] && isset($_GET['currentForm']) && $formname = $_GET['currentForm']) {
			$payment = $this->createPayment($data, $form, $request);
			DB::getConn()->startTransaction();
			try{
				$payment->TxnType = "Purchase";
				$payment->write();
			
				$payment->purchase($data);
				DB::getConn()->endTransaction();
			}catch(Exception $e){
				DB::getConn()->transactionRollback();
				$this->handleError($payment, $e);
			}
			$this->showPayment($payment);
		}
	}
	
	function doRefundPayment($data, $form, $request){
		if(isset($_GET['currentMethod']) && $_GET['currentMethod'] && isset($_GET['currentForm']) && $formname = $_GET['currentForm']) {
			$payment = $this->createPayment($data, $form, $request);
			DB::getConn()->startTransaction();
			try{
				$refunded = $payment->RefundedFor();
				$payment->TxnType = "Refund";
				$payment->MerchantReference = "Refund for: ".$refunded->MerchantReference;
				$payment->write();

				$payment->refund($data);
				DB::getConn()->endTransaction();
			}catch(Exception $e){
				DB::getConn()->transactionRollback();
				$this->handleError($payment, $e);
			}
			$this->showPayment($payment);
		}
	}
	
	function doDPSHostedPayment($data, $form, $request){
		if(isset($_GET['currentMethod']) && $_GET['currentMethod'] && isset($_GET['currentForm']) && $formname = $_GET['currentForm']) {
			$payment = $this->createPayment($data, $form, $request);
			DB::getConn()->startTransaction();
			try{
				$payment->TxnType = "Purchase";
				$query = "?currentMethod=".$_GET['currentMethod']."&currentForm=".$_GET['currentForm'];
				$payment->DPSHostedRedirectURL = "harness/show/".$payment->ClassName."/".$payment->ID.$query;
				$payment->write();
				$payment->dpshostedPurchase($data);
			}catch(Exception $e){
				DB::getConn()->transactionRollback();
				$this->handleError($payment, $e);
				$this->showPayment($payment);
			}
		}
	}

	function doDPSHostedRecurringPayment($data, $form, $request){
		if(isset($_GET['currentMethod']) && $_GET['currentMethod'] && isset($_GET['currentForm']) && $formname = $_GET['currentForm']) {
			$payment = $this->createPayment($data, $form, $request);
			DB::getConn()->startTransaction();
			try{
				$payment->TxnType = "Auth";
				$payment->AuthAmount = 1.00;
				$query = "?currentMethod=".$_GET['currentMethod']."&currentForm=".$_GET['currentForm'];
				$payment->DPSHostedRedirectURL = "harness/show/".$payment->ClassName."/".$payment->ID.$query;
				$payment->write();
				$payment->recurringAuth($data);
			}catch(Exception $e){
				DB::getConn()->transactionRollback();
				$this->handleError($payment, $e);
				$this->showPayment($payment);
			}
		}
	}
	
	function doMerchantHostedRecurringPayment($data, $form, $request){
		if(isset($_GET['currentMethod']) && $_GET['currentMethod'] && isset($_GET['currentForm']) && $formname = $_GET['currentForm']) {
			$payment = $this->createPayment($data, $form, $request);
			DB::getConn()->startTransaction();
			try{
				$payment->AuthAmount = 1.00;

				$payment->write();
				$query = "?currentMethod=".$_GET['currentMethod']."&currentForm=".$_GET['currentForm'];
				$payment->DPSHostedRedirectURL = "harness/show/".$payment->ClassName."/".$payment->ID.$query;
				$payment->write();
				
				$payment->merchantRecurringAuth($data);
				DB::getConn()->endTransaction();
			}catch(Exception $e){
				DB::getConn()->transactionRollback();
				$this->handleError($payment, $e);
			}
			$this->showPayment($payment);
		}
	}
}
?>