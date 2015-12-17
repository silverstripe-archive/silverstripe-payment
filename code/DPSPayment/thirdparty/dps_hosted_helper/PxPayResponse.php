<?php
#******************************************************************************
# Class for PxPay response messages.
#******************************************************************************

class PxPayResponse extends PxPayMessage
{
    public $Success;
    public $StatusRequired;
    public $Retry;
    public $AuthCode;
    public $AmountSettlement;
    public $CurrencySettlement;
    public $CardName;
    public $CardNumber;
    public $CardHolderName;
    public $CurrencyInput;
    public $UserId;
    public $ResponseText;
    public $DpsTxnRef;
    public $MerchantTxnId;
    public $DateExpiry;
    public $TS;
  
    public function PxPayResponse($xml)
    {
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
    public function getTS()
    {
        return $this->TS;
    }
    public function getMerchantTxnId()
    {
        return $this->MerchantTxnId;
    }
    
    public function getResponseText()
    {
        return $this->ResponseText;
    }
    public function getUserId()
    {
        return $this->UserId;
    }
    public function getCurrencyInput()
    {
        return $this->CurrencyInput;
    }
    public function getCardName()
    {
        return $this->CardName;
    }
    public function getCardNumber()
    {
        return $this->CardNumber;
    }
    public function getCardHolderName()
    {
        return $this->CardHolderName;
    }
    public function getDateExpiry()
    {
        return $this->DateExpiry;
    }
    public function getCurrencySettlement()
    {
        $this->CurrencySettlement;
    }
    public function getAmountSettlement()
    {
        return $this->AmountSettlement;
    }
    public function getSuccess()
    {
        return $this->Success;
    }
    public function getStatusRequired()
    {
        return $this->StatusRequired;
    }
    public function getRetry()
    {
        return $this->Retry;
    }
    public function getAuthCode()
    {
        return $this->AuthCode;
    }
    #******************************************************************************
    # Return the expired time, i.e. 2 days ago (GMT/UTC).
    #JZ2004-08-30
    #******************************************************************************
    public function getExpiredTS()
    {
        return gmstrftime("%Y%m%d%H%M%S", time()- 2 * 24 * 60 * 60);
    }
    
    public function getTxnId()
    {
        return $this->MerchantTxnId;
    }
}
