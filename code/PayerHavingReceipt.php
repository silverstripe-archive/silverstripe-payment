<?php

class PayerHavingReceipt extends DataObjectDecorator{
	function extraStatics(){
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