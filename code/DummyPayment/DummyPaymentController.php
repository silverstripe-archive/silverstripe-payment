<?php

/**
 * Controller for DummyPayment
 */
class DummyPaymentController extends PaymentController {
  /**
   * Process the dummy payment method
   */
  function processPayment($data, $form) {
    $this->Status = 'Pending';
    $this->Message = '<p class="warningMessage">' . _t('ChequePayment.MESSAGE', 'Payment accepted via Cheque. Please note : products will not be shipped until payment has been received.') . '</p>';
  
    $this->write();
    return new Payment_Success();
  }
}