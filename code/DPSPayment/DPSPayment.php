<?php

/**
 * Payment type to support credit-card payments through DPS.
 * 	
 * @package payment
 */	
class DPSPayment extends Payment {
	static $db = array(
		'TxnRef' => 'Text',
		'TxnType' => "Enum('Purchase,Auth,Complete,Refund,Validate', 'Purchase')",
		'AuthCode' => 'Varchar(22)',
		'MerchantReference' => 'Varchar(64)',
		'DPSHostedRedirectURL' => 'Text',
		
		// This field is stored for only when the payment is made thru merchant-hosted DPS gateway (PxPost);
		'SettlementDate' => 'Date',
		
		// We store the whole raw response xml in case that tracking back the payment is needed in a later stage for whatever the reason.
		'ResponseXML' => "Text",
	);
	
	static $has_one = array(
		//in case that TxnType is Complete, the DPSPayment could have one Auth DPSPayment
		'AuthPayment' => 'DPSPayment',
		
		//in case that TxnType is Refund, the DPSPayment could have one Refunded DPSPayment
		'RefundedFor' => 'DPSPayment'
	);
	
	private static $input_elements = array(
		'Amount',
		'CardHolderName',
		'CardNumber',
		'BillingId',
		'Cvc2',
		'DateExpiry',
		'DpsBillingId',
		'DpsTxnRef',
		'EnableAddBillCard',
		'InputCurrency',
		'MerchantReference',
		'PostUsername',
		'PostPassword',
		'TxnType',
		'TxnData1',
		'TxnData2',
		'TxnData3',
		'TxnId',
		'EnableAvsData',
		'AvsAction',
		'AvsPostCode',
		'AvsStreetAddress',
		'DateStart',
		'IssueNumber',
		'Track2',
	);
	
	private static $dpshosted_input_elements = array(
		'PxPayUserId',
		'PxPayKey',
		'AmountInput',
		'CurrencyInput',
		'EmailAddress',
		'EnableAddBillCard',
		'MerchantReference',
		'TxnData1',
		'TxnData2',
		'TxnData3',
		'TxnType',
		'TxnId',
		'UrlFail',
		'UrlSuccess',
	);
	
	protected static $testable_form = array(
		"AuthForm" => "Authorising Payment Form",
		"CompleteForm" => "Completing Payment Form",
		"PurchaseForm" => "Merchant-hosted Payment Form",
		"RefundForm" => "Refund Payment Form",
		"DPSHostedForm" => "DPS-hosted Payment Form",
	);
	
	static $default_sort = "ID DESC";
	
	function getPaymentFormFields() {
		$adapter = new DPSAdapter();
		return $adapter->getPaymentFormFields();
	}

	/**
	 * Returns the required fields to add to the order form, when using this payment method. 
	 */
	function getPaymentFormRequirements() {
		$adapter = new DPSAdapter();
		return $adapter->getPaymentFormRequirements();
	}
	
	//This function is hooked with OrderForm/E-commerce at the moment, so we need to keep it as it is.
	function processPayment($data, $form) {
		$inputs['Amount'] = $this->Amount->Amount;
		$inputs['InputCurrency'] = $this->Amount->Currency;
		$inputs['TxnData1'] = $this->ID;
		$inputs['TxnType'] = 'Purchase';
		
		$inputs['CardHolderName'] = $data['CardHolderName'];
		$inputs['CardNumber'] = implode('', $data['CardNumber']);
		$inputs['DateExpiry'] = $data['DateExpiry'];
		if(self::$cvn_mode) $inputs['Cvc2'] = $data['Cvc2'] ? $data['Cvc2'] : '';
		
		$adapter = new DPSAdapter();
		$responseFields = $adapter->doPayment($inputs);
		$adapter->ProcessResponse($this, $responseFields);

		if($this->Status == 'Success'){
			$result = new Payment_Success();
		} else {
			$result = new Payment_Failure();
		}

		return $result;
	}
	
	function getForm($formType){
		$adapter = new DPSAdapter();
		return $adapter->getFormByName($formType);
	}
	

	function getTestableForms(){
		return self::$testable_form;
	}
	
	/**
	 * called by a harness form submission
	 */
	function auth($data){
		DB::getConn()->transactionStart();
		try{
			$this->TxnType = "Auth";
			$this->write();

			$adapter = new DPSAdapter();
			$inputs = $this->prepareAuthInputs($data);
			$adapter->doPayment($inputs, $this);
			DB::getConn()->transactionEnd();
		}catch(Exception $e){
			DB::getConn()->transactionRollback();
			$this->handleError($e);
		}
	}
	
	private function prepareAuthInputs($data){
		//never put this loop after $inputs['AmountInput'] = $this->Amount->Amount;, since it will change it to an array.
		foreach($data as $element => $value){
			if(in_array($element, self::$input_elements)){
				$inputs[$element] = $value;
			}
		}
		$inputs['TxnData1'] = $this->ID;
		$inputs['TxnType'] = $this->TxnType;
		$inputs['Amount'] = $this->Amount->Amount;
		$inputs['InputCurrency'] = $this->Amount->Currency;
		//special element
		$inputs['CardNumber'] = implode('', $data['CardNumber']);
		
		return $inputs;
	}
	
	function complete(){
		DB::getConn()->transactionStart();
		try{
			$auth = $this->AuthPayment();
			$this->TxnType = "Complete";
			$this->MerchantReference = "Complete: ".$auth->MerchantReference;
			$this->write();
		
			$adapter = new DPSAdapter();
			$inputs = $this->prepareCompleteInputs();
			$adapter->doPayment($inputs, $this);
			DB::getConn()->transactionEnd();
		}catch(Exception $e){
			DB::getConn()->transactionRollback();
			$this->handleError($e);
		}
	}
	
	private function prepareCompleteInputs(){
		$auth = $this->AuthPayment();
		$inputs['TxnData1'] = $this->ID;
		$inputs['TxnType'] = $this->TxnType;
		$inputs['Amount'] = $this->Amount->Amount;
		$inputs['InputCurrency'] = $this->Amount->Currency;
		$inputs['DpsTxnRef'] = $auth->TxnRef;
		//$inputs['AuthCode'] = $auth->AuthCode;
		return $inputs;
	}
	
	function purchase($data){
		DB::getConn()->transactionStart();
		try{
			$this->TxnType = "Purchase";
			$this->write();
		
			$adapter = new DPSAdapter();
			$inputs = $this->prepareAuthInputs($data);
			$adapter->doPayment($inputs, $this);
			DB::getConn()->transactionEnd();
		}catch(Exception $e){
			DB::getConn()->transactionRollback();
			$this->handleError($e);
		}
	}
	
	function refund(){
		DB::getConn()->transactionStart();
		try{
			$refunded = $this->RefundedFor();
			$this->TxnType = "Refund";
			$this->MerchantReference = "Refund for: ".$refunded->MerchantReference;
			$this->write();

			$adapter = new DPSAdapter();
			$inputs = $this->prepareRefundInputs();
			$adapter->doPayment($inputs, $this);
			DB::getConn()->transactionEnd();
		}catch(Exception $e){
			DB::getConn()->transactionRollback();
			$this->handleError($e);
		}
	}
	
	private function prepareRefundInputs(){	
		$refundedFor = $this->RefundedFor();
		$inputs['TxnData1'] = $this->ID;
		$inputs['TxnType'] = $this->TxnType;
		$inputs['Amount'] = $this->Amount->Amount;
		$inputs['InputCurrency'] = $this->Amount->Currency;
		$inputs['DpsTxnRef'] = $refundedFor->TxnRef;
		return $inputs;
	}
	
	function dpshostedPurchase($data = array()) {
		DB::getConn()->transactionStart();
		try {
			$this->TxnType = "Purchase";
			$this->write();
			$adapter = new DPSAdapter();
			$inputs = $this->prepareDPSHostedRequest($data);
			
			return $adapter->doDPSHostedPayment($inputs, $this);
		} catch(Exception $e) {
			DB::getConn()->transactionRollback();
			$this->handleError($e);
		}
	}

	public function prepareDPSHostedRequest($data = array()) {
		//never put this loop after $inputs['AmountInput'] = $amount, since it will change it to an array.
		foreach($data as $element => $value) {
			if(in_array($element, self::$dpshosted_input_elements)) {
				$inputs[$element] = $value;
			}
		}

		$inputs['TxnData1'] = $this->ID;
		$inputs['TxnType'] = $this->TxnType;
		$amount = (float) ltrim($this->Amount->Amount, '$');
		$inputs['AmountInput'] = $amount;
		$inputs['InputCurrency'] = $this->Amount->Currency;
		$inputs['MerchantReference'] = $this->MerchantReference;

		$postProcess_url = Director::absoluteBaseURL() ."DPSAdapter/processDPSHostedResponse";
		$inputs['UrlFail'] = $postProcess_url;
		$inputs['UrlSuccess'] = $postProcess_url;

		return $inputs;
	}

	function payAsRecurring() {
		$adapter = new DPSAdapter();
		$inputs = $this->prepareAsRecurringPaymentInputs();
		$adapter->doPayment($inputs, $this);
	}
	
	function prepareAsRecurringPaymentInputs(){
		$reccurringPayment = DataObject::get_by_id('DPSRecurringPayment', $this->RecurringPaymentID);
		$inputs['DpsBillingId'] = $reccurringPayment->DPSBillingID;
		$inputs['TxnData1'] = $this->ID;
		$inputs['TxnType'] = 'Purchase';
		$amount = (float) ltrim($reccurringPayment->Amount->Amount, '$');
		$inputs['Amount'] = $amount;
		$inputs['InputCurrency'] = $reccurringPayment->Amount->Currency;
		$inputs['MerchantReference'] = $reccurringPayment->MerchantReference;
		
		return $inputs;
	}
	
	function CanComplete(){
		$successComplete = $this->successCompletePayment();
		return !($successComplete && $successComplete->ID) && $this->TxnType == 'Auth' && $this->Status = 'Success';
	}
	
	function successCompletePayment() {
		return DataObject::get_one("DPSPayment", "\"Status\" = 'Success' AND \"TxnType\" = 'Complete' AND \"AuthPaymentID\" = '".$this->ID."'");
	}
	
	
	function onAfterWrite(){
		if($this->isChanged('Status') && $this->Status == 'Success'){
			$this->sendReceipt();
		}
		parent::onAfterWrite();
	}
	
	function sendReceipt(){
		$member = $this->PaidBy();
		if($member->exists() && $member->Email){
			$from = DPSAdapter::get_receipt_from();
			if($from){
				$body =  $this->renderWith($this->ClassName."_receipt");
				$body .= $member->ReceiptMessage();
				$email = new Email($from, $member->Email, "Payment receipt (Ref no. #".$this->ID.")", $body);
				$email->send();
			}
		}
	}
}