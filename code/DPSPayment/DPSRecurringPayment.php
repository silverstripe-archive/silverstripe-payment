<?php

class DPSRecurringPayment extends RecurringPayment{
	
	//Note: as a DPS Recurring Payment, its TxnType should be always Auth
	public static $db = array(
		'TxnRef' => 'Text',
		'AuthCode' => 'Varchar(22)',
		'MerchantReference' => 'Varchar(64)',
		'DPSHostedRedirectURL' => 'Text',
		'DPSBillingID' => "Varchar(16)",
		'AuthAmount' => 'Decimal',
		
		// We store the whole raw response xml in case that tracking back the payment is needed in a later stage for whatever the reason.
		'ResponseXML' => "Text",
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
		"DPSHostedRecurringForm" => "DPS-hosted Recurring Payment Form",
		"MerchantHostedRecurringForm" => "Merchant-hosted Recorring Payment Form",
	);
	
	static $default_sort = "ID DESC";
	
	function getTestableForms(){
		return self::$testable_form;
	}
	
	function getForm($formType){
		$adapter = new DPSAdapter();
		return $adapter->getFormByName($formType);
	}
	
	function recurringAuth($data){
		DB::getConn()->transactionStart();
		try{
			$this->TxnType = "Auth";
			$this->AuthAmount = 1.00;
			$this->write();
			$adapter = new DPSAdapter();
			$inputs = $this->prepareDPSHostedRecurringAuthRequest($data);
			$adapter->doDPSHostedPayment($inputs, $this);
		}catch(Exception $e){
			DB::getConn()->transactionRollback();
			$this->handleError($e);
		}
	}
	
	function prepareDPSHostedRecurringAuthRequest($data){
		//never put this loop after $inputs['AmountInput'] = $amount, since it will change it to an array.
		foreach($data as $element => $value){
			if(in_array($element, self::$dpshosted_input_elements)){
				$inputs[$element] = $value;
			}
		}
		
		$inputs['TxnData1'] = $this->ID;
		$inputs['TxnType'] = 'Auth';
		$inputs['EnableAddBillCard'] = 1;
		$inputs['AmountInput'] = $this->AuthAmount;
		$inputs['InputCurrency'] = $this->Amount->Currency;
		$inputs['MerchantReference'] = $this->MerchantReference;

		$postProcess_url = Director::absoluteBaseURL() ."DPSAdapter/processDPSHostedResponse";
		$inputs['UrlFail'] = $postProcess_url;
		$inputs['UrlSuccess'] = $postProcess_url;
		
		return $inputs;
	}
	
	function merchantRecurringAuth($data){
		DB::getConn()->transactionStart();
		try{
			$this->AuthAmount = 1.00;
			$this->write();
			
			$adapter = new DPSAdapter();
			$inputs = $this->prepareMerchantHostedRecurringAuthInputs($data);
			$adapter->doPayment($inputs, $this);
			DB::getConn()->transactionEnd();
		}catch(Exception $e){
			DB::getConn()->transactionRollback();
			$this->handleError($e);
		}
	}
	
	function prepareMerchantHostedRecurringAuthInputs($data){
		//never put this loop after $inputs['AmountInput'] = $this->Amount->Amount;, since it will change it to an array.
		foreach($data as $element => $value){
			if(in_array($element, self::$input_elements)){
				$inputs[$element] = $value;
			}
		}
		$inputs['TxnData1'] = $this->ID;
		$inputs['TxnType'] = 'Validate';
		$inputs['EnableAddBillCard'] = 1;
		$inputs['Amount'] = $this->AuthAmount;
		$inputs['InputCurrency'] = $this->Amount->Currency;
		$inputs['MerchantReference'] = $this->MerchantReference;
		//special element
		$inputs['CardNumber'] = implode('', $data['CardNumber']);
		return $inputs;
	}
	
	function HarnessPayNextLink(){
		return 'harness/paynext/'.$this->ClassName."/".$this->ID;
	}
	
	function getNextPayment(){
		$next = parent::getNextPayment();
		$next->ClassName = 'DPSPayment';
		$next->RecordClassName = 'DPSPayment';
		$next->TxnType = 'Purchase';
		$next->MerchantReference = $this->MerchantReference;
		$next->write();
		return DataObject::get_by_id('DPSPayment', $next->ID);
	}
}

?>