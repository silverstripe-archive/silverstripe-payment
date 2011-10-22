<?php
/**
 * @package payment_dpshosted
 * @see http://civicrm.org
 */
#******************************************************************************
# Class for PxPay response messages.
#******************************************************************************

class PxPayResponse extends PxPayMessage
{
	var $Success;
	var $StatusRequired;
	var $Retry;
	var $AuthCode;
	var $AmountSettlement;
	var $CurrencySettlement;
	var $CardName;
	var $CardNumber;
	var $CardHolderName;
	var $CurrencyInput;
	var $UserId;
	var $ResponseText;
	var $DpsTxnRef;
	var $MerchantTxnId;
	var $DateExpiry;
	var $TS;
  
	function PxPayResponse($xml){
		$msg = new MifMessage($xml);
		$this->PxPayMessage();
		
	
		$this->setBillingId($msg->get_element_text("BillingId"));
		$this->setDpsBillingId($msg->get_element_text("DpsBillingId"));
		$this->setEmailAddress($msg->get_element_text("EmailAddress"));
		$this->setMerchantReference($msg->get_element_text("MerchantReference"));
		$this->setTxnData1($msg->get_element_text("TxnData1"));
		$this->setTxnData2($msg->get_element_text("TxnData2"));
		$this->setTxnData3($msg->get_element_text("TxnData3"));
		$this->setTxnType($msg->get_element_text("TxnType"));		
		$this->Success = $msg->get_element_text("Success");
		$this->AuthCode = $msg->get_element_text("AuthCode");
		$this->AmountSettlement = $msg->get_element_text("AmountSettlement");
		$this->CurrencySettlement = $msg->get_element_text("CurrencySettlement");
		$this->CardName = $msg->get_element_text("CardName");
		$this->ResponseText = $msg->get_element_text("ResponseText");
		$this->DpsTxnRef = $msg->get_element_text("DpsTxnRef");
		$this->MerchantTxnId = $msg->get_element_text("TxnId");
		$this->CardHolderName = $msg->get_element_text("CardHolderName");
		$this->CardNumber = $msg->get_element_text("CardNumber");
		$this->DateExpiry = $msg->get_element_text("DateExpiry");
		$this->CurrencyInput = $msg->get_element_text("CurrencyInput");
		
	}
	function getTS(){
		return $this->TS;
	}
	function getMerchantTxnId(){
		return $this->MerchantTxnId;
	}
	
	function getResponseText(){
		return $this->ResponseText;
	}
	function getUserId(){
		return $this->UserId;
	}
	function getCurrencyInput(){
		return $this->CurrencyInput;
	}
	function getCardName(){
		return $this->CardName;
	}
	function getCardNumber() {
		return $this->CardNumber;
	}
	function getCardHolderName() {
		return $this->CardHolderName;
	}
	function getDateExpiry() {
		return $this->DateExpiry;
	}
	function getCurrencySettlement(){
		$this->CurrencySettlement;
	}
	function getAmountSettlement(){
		return $this->AmountSettlement;
	}
	function getSuccess(){
		return $this->Success;
	}
	function getStatusRequired(){
		return $this->StatusRequired;
	}
	function getRetry(){
		return $this->Retry;
	}
	function getAuthCode(){
		return $this->AuthCode;
	}
	#******************************************************************************
	# Return the expired time, i.e. 2 days ago (GMT/UTC).
	#JZ2004-08-30
	#******************************************************************************
	function  getExpiredTS()
	{
	  
	  return gmstrftime("%Y%m%d%H%M%S", time()- 2 * 24 * 60 * 60);
	}
	
	function getTxnId(){
		return $this->MerchantTxnId;
	}
	
}