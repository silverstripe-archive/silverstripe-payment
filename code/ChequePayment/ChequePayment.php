<?php

/**
 * Payment object representing a cheque payment.
 * 
 * @package payment
 */
class ChequePayment extends Payment
{
    
    /**
     * Process the Cheque payment method
     */
    public function processPayment($data, $form)
    {
        $this->Status = 'Pending';
        $this->Message = '<p class="warningMessage">' . _t('ChequePayment.MESSAGE', 'Payment accepted via Cheque. Please note : products will not be shipped until payment has been received.') . '</p>';
        
        $this->write();
        return new Payment_Success();
    }
    
    public function getPaymentFormFields()
    {
        return new FieldSet(
            // retrieve cheque content from the ChequeContent() method on this class
            new LiteralField("Chequeblurb", '<div id="Cheque" class="typography">' . $this->ChequeContent() . '</div>'),
            new HiddenField("Cheque", "Cheque", 0)
        );
    }
    
    public function getPaymentFormRequirements()
    {
        return null;
    }

    /**
     * Returns the Cheque content from the CheckoutPage
     */
    public function ChequeContent()
    {
        if (class_exists('CheckoutPage')) {
            return DataObject::get_one('CheckoutPage')->ChequeMessage;
        }
    }
}
