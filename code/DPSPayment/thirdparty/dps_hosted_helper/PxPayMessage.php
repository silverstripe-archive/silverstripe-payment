<?php
#******************************************************************************
# Abstract base class for PxPay messages.
# These are messages with certain defined elements,  which can be serialized to XML.

#******************************************************************************
class PxPayMessage
{
    public $TxnType;
    public $TxnData1;
    public $TxnData2;
    public $TxnData3;
    public $MerchantReference;
    public $EmailAddress;
    public $BillingId;
    public $DpsBillingId;
    public $DpsTxnRef;
    
    public function PxPayMessage()
    {
    }
    public function setDpsTxnRef($DpsTxnRef)
    {
        $this->DpsTxnRef = $DpsTxnRef;
    }
    
    public function getDpsTxnRef()
    {
        return $this->DpsTxnRef;
    }
    
    public function setDpsBillingId($DpsBillingId)
    {
        $this->DpsBillingId = $DpsBillingId;
    }
    
    public function getDpsBillingId()
    {
        return $this->DpsBillingId;
    }
    public function setBillingId($BillingId)
    {
        $this->BillingId = $BillingId;
    }
    
    public function getBillingId()
    {
        return $this->BillingId;
    }
    public function setTxnType($TxnType)
    {
        $this->TxnType = $TxnType;
    }
    public function getTxnType()
    {
        return $this->TxnType;
    }
    public function setMerchantReference($MerchantReference)
    {
        $this->MerchantReference = $MerchantReference;
    }
    
    public function getMerchantReference()
    {
        return $this->MerchantReference;
    }
    public function setEmailAddress($EmailAddress)
    {
        $this->EmailAddress = $EmailAddress;
    }
    
    public function getEmailAddress()
    {
        return $this->EmailAddress;
    }
    
    public function setTxnData1($TxnData1)
    {
        $this->TxnData1 = $TxnData1;
    }
    public function getTxnData1()
    {
        return $this->TxnData1;
    }
    public function setTxnData2($TxnData2)
    {
        $this->TxnData2 = $TxnData2;
    }
    public function getTxnData2()
    {
        return $this->TxnData2;
    }
    
    public function getTxnData3()
    {
        return $this->TxnData3;
    }
    public function setTxnData3($TxnData3)
    {
        $this->TxnData3 = $TxnData3;
    }
    public function toXml()
    {
        $arr = get_object_vars($this);
        $root = strtolower(get_class($this));
#echo "<br>root:".$root;
        if ($root == "pxpayrequest") {
            $root = "GenerateRequest";
        } elseif ($root == "pxpayresponse") {
            $root = "Response";
        } else {
            $root ="Request";
        }

            
        $xml  = "<$root>";
        while (list($prop, $val) = each($arr)) {
            $xml .= "<$prop>$val</$prop>" ;
        }

        $xml .= "</$root>";
        
        return $xml;
    }
}
