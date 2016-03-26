<?php

class PayerHavingReceipt extends DataExtension
{
    public function extraStatics($class = NULL, $extension = NULL)
    {
        return array(
            'db' => array(
                'Street' =>        'Varchar',
                'Suburb' =>        'Varchar',
                'CityTown' =>    'Varchar',
                'Country' =>    'Varchar',
            ),
        );
    }
    
    public function ReceiptMessage()
    {
        return $this->owner->renderWith('Payer_receipt');
    }
}
