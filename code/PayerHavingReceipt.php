<?php

class PayerHavingReceipt extends DataExtension{
	function extraStatics($class = null, $extension = null){
		return array(
			'db' => array(
				'Street' =>		'Varchar',
				'Suburb' =>		'Varchar',
				'CityTown' =>	'Varchar',
				'Country' =>	'Varchar',
			),
		);
	}
	
	function ReceiptMessage(){
		return $this->owner->renderWith('Payer_receipt');
	}
}
