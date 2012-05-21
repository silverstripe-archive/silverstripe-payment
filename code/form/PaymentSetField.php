<?php

class PaymentSetField extends OptionsetField {
	
	protected $template = "PaymentSetField";	

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

  function FieldHolder() {
		return $this->renderWith($this->template);
	}
	
}