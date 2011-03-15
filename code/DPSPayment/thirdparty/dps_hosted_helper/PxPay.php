<?php
/**
 * @package payment_dpshosted
 * @see http://civicrm.org
 */
class PxPay
{
	var $PxPay_Key;
	var $PxPay_Url;
	var $PxPay_Userid;
	function PxPay($Url, $UserId, $Key){
		//error_reporting(E_ERROR);
		$this->PxPay_Key = $Key;#pack("H*", $Key);
		$this->PxPay_Url = $Url;
		$this->PxPay_Userid = $UserId;
	}
	
	#******************************************************************************
	# Create an encoded request for the PxPay Host.
	#******************************************************************************
	function makeRequest($request)
	{		
		#Validate the Request
		if($request->validData() == false) return "" ;
			
  	//	$txnId = uniqid("MI");  #You need to generate you own unqiue reference. 
	//	$request->setTxnId($txnId);
		$request->setTs($this->getCurrentTS());
		$request->setSwVersion("1.0");
		$request->setAppletType("PHPPxPay");		
		$request->setUserId($this->PxPay_Userid);
		$request->setKey($this->PxPay_Key);
		$xml = $request->toXml();
		
		// parsing the given URL
       $URL_Info=parse_url($this->PxPay_Url);

       // Building referrer
       if(!isset($referrer)||$referrer=="") // if not given use this script as referrer
         $referrer=$_SERVER["SCRIPT_NAME"];

       // Find out which port is needed - if not given use standard (=80)
       if(!isset($URL_Info["port"]))
			$URL_Info["port"]=443;
		#	$URL_Info["port"]=80;

	  // building POST-request:
       $requestdata ="POST ".$URL_Info["path"]." HTTP/1.1\n";
       $requestdata.="Host: ".$URL_Info["host"]."\n";
	#   $requestdata.="User-Agent: PHP Script\n";
       $requestdata.="Referer: $referrer\n";
       $requestdata.="Content-type: application/x-www-form-urlencoded\n";
       $requestdata.="Content-length: ".strlen($xml)."\n";
       $requestdata.="Connection: close\n";
       $requestdata.="\n";
       $requestdata.=$xml."\n";
	   $fp = fsockopen("ssl://".$URL_Info["host"],$URL_Info["port"]);
	  # $fp = fsockopen($URL_Info["host"],$URL_Info["port"]);
	 
       fputs($fp, $requestdata);
	   $result = '';
       while(!feof($fp)) {
           $result .= fgets($fp, 128);
       }
       fclose($fp);	
	   $result = strstr($result, "<Request");
		return $result;
		
	}
			
	#******************************************************************************
	# Return the decoded response from the PxPay Host.
	#******************************************************************************
	function getResponse($resp_enc){
			
		
		$xml = "<ProcessResponse><PxPayUserId>".$this->PxPay_Userid."</PxPayUserId><PxPayKey>".$this->PxPay_Key."</PxPayKey><Response>".$resp_enc."</Response></ProcessResponse>";
		// parsing the given URL
       $URL_Info=parse_url($this->PxPay_Url);
       // Building referrer
       if(!isset($referrer)||$referrer=="") // if not given use this script as referrer
         $referrer=$_SERVER["SCRIPT_NAME"];

       // Find out which port is needed - if not given use standard (=80)
       if(!isset($URL_Info["port"]))
         $URL_Info["port"]=443;
		#	$URL_Info["port"]=80;

       // building POST-request:
       $requestdata ="POST ".$URL_Info["path"]." HTTP/1.1\n";
       $requestdata.="Host: ".$URL_Info["host"]."\n";
       $requestdata.="Referer: $referrer\n";
       $requestdata.="Content-type: application/x-www-form-urlencoded\n";
       $requestdata.="Content-length: ".strlen($xml)."\n";
       $requestdata.="Connection: close\n";
       $requestdata.="\n";
       $requestdata.=$xml."\n";
	  $fp = fsockopen("ssl://".$URL_Info["host"],$URL_Info["port"]);
	  # $fp = fsockopen($URL_Info["host"],$URL_Info["port"]);
       fputs($fp, $requestdata);

	   $result = "";
       while(!feof($fp)) {
           $result .= fgets($fp, 128);
       }
       fclose($fp);
	   
		$result = strstr($result, "<Response");
		$pxresp = new PxPayResponse($result);
		return $pxresp;	
	}
	
	#******************************************************************************
	# Return the current time (GMT/UTC).The return time formatted YYYYMMDDHHMMSS.
	#******************************************************************************
	function getCurrentTS()
	{
	  
	  return gmstrftime("%Y%m%d%H%M%S", time());
	}
	
	

}