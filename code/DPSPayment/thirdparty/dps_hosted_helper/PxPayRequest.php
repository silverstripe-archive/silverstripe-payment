<?php
/**
 * @package payment_dpshosted
 * @see http://civicrm.org
 */
#******************************************************************************
# Class for PxPay request messages.
#******************************************************************************
class PxPayRequest extends PxPayMessage
{
	var $TxnId,$UrlFail,$UrlSuccess;
	var $AmountInput, $AppletVersion, $CurrencyInput;
	var $EnableAddBillCard;
	var $TS;
	var $PxPayUserId;
	var $PxPayKey;
	
	var $AppletType;
	
	#Constructor
 	function PxPayRequest(){
		$this->PxPayMessage();
		
	}
	
	function setAppletType($AppletType){
		$this->AppletType = $AppletType;
	}
	
	function getAppletType(){
		return $this->AppletType;
	}
	
	
	
	function setTs($Ts){
		$this->TS = $Ts;
	}
	function setEnableAddBillCard($EnableBillAddCard){
	 $this->EnableAddBillCard = $EnableBillAddCard;
	}
	
	function getEnableAddBillCard(){
		return $this->EnableAddBillCard;
	}
	function setInputCurrency($InputCurrency){
		$this->CurrencyInput = $InputCurrency;
	}
	function getInputCurrency(){
		return $this->CurrencyInput;
	}
	function setTxnId( $TxnId)
	{
		$this->TxnId = $TxnId;
	}
	function getTxnId(){
		return $this->TxnId;
	}
	
	function setUrlFail($UrlFail){
		$this->UrlFail = $UrlFail;
	}
	function getUrlFail(){
		return $this->UrlFail;
	}
	function setUrlSuccess($UrlSuccess){
		$this->UrlSuccess = $UrlSuccess;
	}
	function setAmountInput($AmountInput){
		$this->AmountInput = sprintf("%9.2f",$AmountInput); 
	}
	
	function getAmountInput(){
			
		return $this->AmountInput;
	}
	function setUserId($UserId){
		$this->PxPayUserId = $UserId;
	}
	
	function setKey($Key){
		$this->PxPayKey = $Key;
	}
	
	function setOpt($timeOut){
		if($timeOut) $this->Opt = 'TO='.gmdate('ymdHi', $timeOut->format('U'));
	}
	
	function setSwVersion($SwVersion){
		$this->AppletVersion = $SwVersion;
	}
	
	function getSwVersion(){
		return $this->AppletVersion;
	}
	#******************************************************************
	#Data validation 
	#******************************************************************
	function validData(){
		$msg = "";
		if($this->TxnType != "Purchase")
			if($this->TxnType != "Auth")
				if($this->TxnType != "GetCurrRate")
					if($this->TxnType != "Refund")
						if($this->TxnType != "Complete")
							if($this->TxnType != "Order1")
								$msg = "Invalid TxnType[$this->TxnType]<br>";
		
		if(strlen($this->MerchantReference) > 64)
			$msg = "Invalid MerchantReference [$this->MerchantReference]<br>";
		
		if(strlen($this->TxnId) > 16)
			$msg = "Invalid TxnId [$this->TxnId]<br>";
		if(strlen($this->TxnData1) > 255)
			$msg = "Invalid TxnData1 [$this->TxnData1]<br>";
		if(strlen($this->TxnData2) > 255)
			$msg = "Invalid TxnData2 [$this->TxnData2]<br>";
		if(strlen($this->TxnData3) > 255)
			$msg = "Invalid TxnData3 [$this->TxnData3]<br>";
			
		if(strlen($this->EmailAddress) > 255)
			$msg = "Invalid EmailAddress [$this->EmailAddress]<br>";
			
		if(strlen($this->UrlFail) > 255)
			$msg = "Invalid UrlFail [$this->UrlFail]<br>";
		if(strlen($this->UrlSuccess) > 255)
			$msg = "Invalid UrlSuccess [$this->UrlSuccess]<br>";
		if(strlen($this->BillingId) > 32)
			$msg = "Invalid BillingId [$this->BillingId]<br>";
		if(strlen($this->DpsBillingId) > 16)
			$msg = "Invalid DpsBillingId [$this->DpsBillingId]<br>";
			
		if ($msg != "") {
		    trigger_error($msg,E_USER_ERROR);
			return false;
		}
		return true;
	}

}