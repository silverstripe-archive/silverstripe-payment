<?php
/**
 * Dummy payment processor
 */
class DummyProcessor_MerchantHosted extends PaymentProcessor_MerchantHosted {

	/**
	 * Pre-fill the credit card fields with testing data
	 * @see PaymentProcessor_MerchantHosted::getCreditCardFields()
	 */
	public function getCreditCardFields() {

		$months = array_combine(range(1, 12), range(1, 12));
		$years = array_combine(range(date('Y'), date('Y') + 10), range(date('Y'), date('Y') + 10));

		$fieldList = new FieldList();
		$fieldList->push(new DropDownField('CreditCardType', 'Select Credit Card Type :', $this->gateway->getSupportedCardTypes()));
		$fieldList->push(new TextField('FirstName', 'First Name', 'John'));
		$fieldList->push(new TextField('LastName', 'Last Name', 'Doe'));
		$fieldList->push(new CreditCardField('CardNumber', 'Credit Card Number :', '4111111111111111'));
		$fieldList->push(new DropDownField('MonthExpiry', 'Expiration Month: ', $months, '12'));
		$fieldList->push(new DropDownField('YearExpiry', 'Expiration Year: ', $years, date('Y') + 1));
		$fieldList->push(new TextField('Cvc2', 'Credit Card CVN : (3 or 4 digits)', '123', 4));

		return $fieldList;
	}

	/**
	 * Get form fields for processing a payment
	 */
	public function getFormFields() {
		$fieldList = parent::getFormFields();

		$amountField = $fieldList->fieldByName('Amount');
		$amountField->setValue('99.00');
		$fieldList->replaceField('Amount', $amountField);

		$fieldList->merge($this->getCreditCardFields());

		return $fieldList;
	}
}

/**
 * Dummy payment processor
 */
class DummyProcessor_GatewayHosted extends PaymentProcessor_GatewayHosted {

	/**
	 * Get form fields for processing a payment
	 */
	public function getFormFields() {
		$fieldList = parent::getFormFields();

		$amountField = $fieldList->fieldByName('Amount');
		$amountField->setValue('88.00');
		$fieldList->replaceField('Amount', $amountField);

		return $fieldList;
	}
}