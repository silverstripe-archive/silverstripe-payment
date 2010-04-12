<?php
/**
 * @package payment_dpshosted
 * @see http://civicrm.org
 */
class PxAccess
{
	var $Mac_Key, $Des_Key;
	var $PxAccess_Url;
	var $PxAccess_Userid;
	function PxAccess($Url, $UserId, $Des_Key, $Mac_Key){
		error_reporting(E_ERROR);
		$this->Mac_Key = pack("H*",$Mac_Key);
		$this->Des_Key = pack("H*", $Des_Key);
		$this->PxAccess_Url = $Url;
		$this->PxAccess_Userid = $UserId;
	}
	function makeRequest($request)
	{
		#Validate the REquest
		if($request->validData() == false) return "" ;
			
  		#$txnId=rand(1,100000);
		//$txnId = uniqid("MI");  #You need to generate you own unqiue reference. JZ:2004-08-12
		//$request->setTxnId($txnId);
		$request->setTs($this->getCurrentTS());
		$request->setSwVersion("2.01.01");
		$request->setAppletType("PHPPxAccess");
		
		
		$xml = $request->toXml();
		
	  if (strlen($xml)%8 != 0)
	  {
	    $xml = str_pad($xml, strlen($xml) + 8-strlen($xml)%8); # pad to multiple of 8
	  }
	  #add MAC code JZ2004-8-16
	  $mac = $this->makeMAC($xml,$this->Mac_Key );
	  $msg = $xml.$mac;
	  #$msg = $xml;
	  $enc = $this->encrypt_tripledes($msg, $this->Des_Key); #JZ2004-08-16: Include the MAC code
	  
	  $enclen = strlen($enc) * 2;
	  
	  $enc_hex = unpack("H$enclen", $enc); #JZ2005-03-14: there is a bug in the new version php unpack function 
	  #$enc_hex = @unpack("H*", $enc); #JZ2005-03-14: there is a bug in the new version php unpack function 
	  
	  #$enc_hex = $enc_hex[""]; #use this function if PHP version before 4.3.4
	   #$enc_hex = $enc_hex[1]; #use this function if PHP version after 4.3.4
	  $enc_hex = (version_compare(PHP_VERSION, "4.3.4", ">=")) ? $enc_hex[1] :$enc_hex[""];
	 
	  $PxAccess_Redirect = "$this->PxAccess_Url?userid=$this->PxAccess_Userid&request=$enc_hex";
  
		return $PxAccess_Redirect;
		
	}
		
	#******************************************************************************
	# This function ecrypts data using 3DES via libmcrypt
	#******************************************************************************
	function encrypt_tripledes($data, $key)
	{
	# deprecated libmcrypt 2.2 encryption: use this if you have libmcrypt 2.2.x
	# $result = mcrypt_ecb(MCRYPT_DES, $key, $data, MCRYPT_ENCRYPT);
	# return $result;
	#
	# otherwise use this for libmcrypt 2.4.x and above:
	  $td = mcrypt_module_open('tripledes', '', 'ecb', '');
	  $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
	  mcrypt_generic_init($td, $key, $iv);
	  $result = mcrypt_generic($td, $data);
	  #mcrypt_generic_deinit($td); #Might cause problem in some PHP version
	  return $result;
	}
	
	
	#******************************************************************************
	# This function decrypts data using 3DES via libmcrypt
	#******************************************************************************
	function decrypt_tripledes($data, $key)
	{
	# deprecated libmcrypt 2.2 encryption: use this if you have libmcrypt 2.2.x
	# $result = mcrypt_ecb(MCRYPT_DES, $key, $data, MCRYPT_DECRYPT);
	# return $result;
	#
	# otherwise use this for libmcrypt 2.4.x and above:
	  $td = mcrypt_module_open('tripledes', '', 'ecb', '');
	  $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
	  mcrypt_generic_init($td, $key, $iv);
	  $result = mdecrypt_generic($td, $data);
	  #mcrypt_generic_deinit($td); #Might cause problem in some PHP version
	  return $result;
	}
	
	#JZ2004-08-16
	
	#******************************************************************************
	# Generate and return a message authentication code (MAC) for a string.
	# (Uses ANSI X9.9 procedure.)
	#******************************************************************************
	function makeMAC($msg,$Mackey){
		
	 if (strlen($msg)%8 != 0)
	  {
	  	$extra = 8 - strlen($msg)%8;
	    $msg .= str_repeat(" ", $extra); # pad to multiple of 8
	  }
	  $mac = pack("C*", 0, 0, 0, 0, 0, 0, 0, 0); # start with all zeros
	  #$mac_result = unpack("C*", $mac);
	  
	   for ( $i=0; $i<strlen($msg)/8; $i++)
	  {
	    $msg8 = substr($msg, 8*$i, 8);
	    
		$mac ^= $msg8;
	    $mac = $this->encrypt_des($mac,$Mackey);
	    
	  }
		#$mac = pack("C*", $mac);
	    #$mac_result= encrypt_des($mac, $Mackey);
		
		$mac_result	= unpack("H8", $mac);
		#$mac_result	= $mac_result[""]; #use this function if PHP version before 4.3.4
		#$mac_result	= $mac_result[1]; #use this function if PHP version after 4.3.4
		$mac_result = (version_compare(PHP_VERSION, "4.3.4", ">=")) ? $mac_result[1]: $mac_result[""];

		return $mac_result;
	  
	   
	}
	 
	#******************************************************************************
	# This function ecrypts data using DES via libmcrypt
	# JZ2004-08-16
	#******************************************************************************
	function encrypt_des($data, $key)
	{
	# deprecated libmcrypt 2.2 encryption: use this if you have libmcrypt 2.2.x
	#  $result = mcrypt_ecb(MCRYPT_3DES, $key, $data, MCRYPT_ENCRYPT);
	#  return $result;
	#
	# otherwise use this for libmcrypt 2.4.x and above:

	  $td = mcrypt_module_open('des', '', 'ecb', '');
	  $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
	  mcrypt_generic_init($td, $key, $iv);
	  $result = mcrypt_generic($td, $data);
	  #mcrypt_generic_deinit($td); #Might cause problem in some PHP version
	  mcrypt_module_close($td);

	  return $result;
	}


	#JZ2004-08-16
	function getResponse($resp_enc){
		#global $Mac_Key;
		$enc = pack("H*", $resp_enc);
		$resp = trim($this->decrypt_tripledes($enc, $this->Des_Key));
		$xml = substr($resp, 0, strlen($resp)-8);
	    $mac = substr($resp, -8);
		$checkmac = $this->makeMac($xml, $this->Mac_Key);
		if($mac != $checkmac){
			$xml = "<success>0</success><ResponseText>Response MAC Invalid</ResponseText>";
		}
		
		$pxresp = new PxPayResponse($xml);
		return $pxresp;
	
	}

	
	
	#******************************************************************************
	# Return the current time (GMT/UTC).The return time formatted YYYYMMDDHHMMSS.
	#JZ2004-08-30
	#******************************************************************************
	function getCurrentTS()
	{
	  
	  return gmstrftime("%Y%m%d%H%M%S", time());
	}
}
?>