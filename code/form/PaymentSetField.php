<?php
/**
 * For displaying a set of modifiers on the {@link CheckoutPage} which will inject their details
 * into {@link Order} {@link Modifications}.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package shop
 * @subpackage form
 * @version 1.0
 */
class PaymentSetField extends OptionsetField {
	
	/**
	 * Template for rendering
	 *
	 * @var String
	 */
	protected $template = "PaymentSetField";	
	
	/**
	 * Creates a new optionset field for order modifers with the naming convention
	 * Modifiers[ClassName] where ClassName is name of modifier class.
	 * 
	 * @param name The field name, needs to be the class name of the class that is going to be the modifier
	 * @param title The field title
	 * @param source An map of the dropdown items
	 * @param value The current value
	 * @param form The parent form
	 */
	function __construct($name, $title = "", $source = array(), $value = "", $form = null) {

		parent::__construct($name, $title, $source, $value, $form);
	}
	
  function Field() {
		$options = '';
		$odd = 0;
		$source = $this->getSource();
		
		foreach ($source as $key => $value) {

			$itemID = $this->id() . "_" . preg_replace('/[^a-zA-Z0-9\-\_]/','_', $key);
			$checked = ($key == $this->value) ? ' checked="checked"' : '';
			$odd = ($odd + 1) % 2;
			$extraClass = $odd ? "odd" : "even";
			$extraClass .= " val" . preg_replace('/[^a-zA-Z0-9\-\_]/','_', $key);
			$disabled = ($this->disabled || in_array($key, $this->disabledItems)) ? 'disabled="disabled"' : '';
			$name = $this->Name();
			
			$extraFields = $this->getPaymentFieldsFor($key);

			$options .= <<<EOF
<li class="$extraClass">
	<input id="$itemID" name="$name" type="radio" value="$key" $checked $disabled class="radio" /> 
	<label for="$itemID">
		<div class="PaymentFieldLabel">
		  $value
		</div>
		<div class="ExtraPaymentFields">
		  $extraFields
		</div>
	</label>
</li>
EOF;

		}
		
		$id = $this->id();
		$extraClass = $this->extraClass();
		
		$field = <<<EOF
<ul id="$id" class="optionset PaymentSet $extraClass">
	$options
</ul>
EOF;
    return $field;
	}
	
	private function getPaymentFieldsFor($methodClass) {

	  $methodFields = new CompositeField(singleton($methodClass)->getPaymentFormFields());
		$methodFields->setID("MethodFields_$methodClass");
		$methodFields->addExtraClass('paymentfields');
		$methodFields->addExtraClass($methodClass.'Fields');
		
		return $methodFields->FieldHolder();
	}

	
	/**
	 * Render field with the appropriate template.
	 * 
	 * @see FormField::FieldHolder()
	 * @return String
	 */
  function FieldHolder() {
		return $this->renderWith($this->template);
	}
	
}