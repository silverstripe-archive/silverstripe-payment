<?php
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
	function makeRequest($request) {
		if($request->validData() == false) return "" ;

		$request->setTs($this->getCurrentTS());
		$request->setSwVersion("1.0");
		$request->setAppletType("PHPPxPay");		
		$request->setUserId($this->PxPay_Userid);
		$request->setKey($this->PxPay_Key);
		$xml = $request->toXml();

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->PxPay_Url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

		$response = curl_exec($ch);

		if(curl_error($ch)) {
			throw new Exception(curl_error($ch));
		}

		return $response;
	}
			
	#******************************************************************************
	# Return the decoded response from the PxPay Host.
	#******************************************************************************
	function getResponse($resp_enc) {
		$xml = "<ProcessResponse><PxPayUserId>".$this->PxPay_Userid."</PxPayUserId><PxPayKey>".$this->PxPay_Key."</PxPayKey><Response>".$resp_enc."</Response></ProcessResponse>";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->PxPay_Url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

		$response = curl_exec($ch);

		if(curl_error($ch)) {
			throw new Exception(curl_error($ch));
		}

		return new PxPayResponse($response);
	}
	
	#******************************************************************************
	# Return the current time (GMT/UTC).The return time formatted YYYYMMDDHHMMSS.
	#******************************************************************************
	function getCurrentTS()
	{
	  
	  return gmstrftime("%Y%m%d%H%M%S", time());
	}
	
	

}