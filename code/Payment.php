<?php 
/**
 * "Abstract" class for a number of different payment
 * types allowing a user to pay for something on a site.
 *
 * @see DPSPayment
 * @see WorldPayPayment
 * @see ChequePayment
 *
 * This can't be an abstract class because sapphire doesn't
 * support abstract DataObject classes.
 * 
 * @package payment
 */
class Payment extends DataObject {

	/**
 	 * Incomplete (default): Payment created but nothing confirmed as successful
 	 * Success: Payment successful
 	 * Failure: Payment failed during process
 	 * Pending: Payment awaiting receipt/bank transfer etc
 	 */
	public static $db = array(
		'Status' => "Enum('Incomplete,Success,Failure,Pending','Incomplete')",
		'Amount' => 'Money',
		'Message' => 'Text',
		'IP' => 'Varchar',
		'ProxyIP' => 'Varchar',
		'PaidForID' => "Int",
		'PaidForClass' => 'Varchar',
		
		//This is used only when the payment is one of the recurring payments, when a scheduler is trying to 
		//find which is the latest one for the recurring payments
		'PaymentDate' => "Date",
		
		//Usered for store any Exception during this payment Process.
		'ExceptionError' => 'Text'
	);
	
	public static $has_one = array(
		'RecurringPayment' => 'RecurringPayment',
		'PaidBy' => 'Member',
	);
	
	/**
	 * Instances of Payment supported (usable) on this site.
	 * @var array
	 */
	protected static $supported_methods = array(
		'ChequePayment' => 'Cheque'
	);
	
	
	/**
	 * Make payment table transactional.
	 */
	static $create_table_options = array(
		'MySQLDatabase' => 'ENGINE=InnoDB'
	);
		
	/**
	 * The currency code used for payments.
	 * @var string
	 */
	protected static $site_currency = 'USD';
	
	/**
	 * the testable form of the payment method
	 */
	protected static $testable_form = array();
	
	/**
	 * Set the currency code that this site uses.
	 * @param string $currency Currency code. e.g. "NZD"
	 */
	public static function set_site_currency($currency) {
		self::$site_currency = $currency;
	}
	
	/**
	 * Return the site currency in use.
	 * @return string
	 */
	public static function site_currency() {
		return self::$site_currency;
	}
	
	/**
	 * Set the payment types that this site supports.
	 * The classes should all be subclasses of Payment.
	 *
	 * @param array $methodMap A map of class names to human-readable descriptions of the payment methods.
	 */
	static function set_supported_methods($methodMap) {
		self::$supported_methods = $methodMap;
	}
	
	function populateDefaults() {
		parent::populateDefaults();
		
		$this->Amount->Currency = Payment::site_currency();
		$this->setClientIP();
 	}
	
	/**
	 * Set the IP address of the user to this payment record.
	 * This isn't perfect - IP addresses can be hidden fairly easily.
	 */
	function setClientIP() {
		$proxy = null;
		$ip = null;
		
		if(isset($_SERVER['HTTP_CLIENT_IP'])) $ip = $_SERVER['HTTP_CLIENT_IP'];
		elseif(isset($_SERVER['REMOTE_ADDR'])) $ip = $_SERVER['REMOTE_ADDR'];
		else $ip = null;
		
		if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$proxy = $ip;
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		
		// Only set the IP and ProxyIP if none currently set
		if(!$this->IP) $this->IP = $ip;
		if(!$this->ProxyIP) $this->ProxyIP = $proxy;
	}
	
	/**
	 * Returns the Payment type currently in use.
	 * @return string
	 */
	function PaymentMethod() {
		if(isset(self::$supported_methods[$this->ClassName])) {
			return self::$supported_methods[$this->ClassName];
		}
	}
	
	/**
	 * Return a set of payment fields from all enabled
	 * payment methods for this site, given the . {@link Payment::set_supported_methods()}
	 * is used to define which methods are available.
	 * 
	 * @return FieldSet
	 */
	static function combined_form_fields($amount) {

		// Create the initial form fields, which defines an OptionsetField
		// allowing the user to choose which payment method to use.
		$fields = new FieldSet(
			new HeaderField(_t('Payment.PAYMENTTYPE', 'Payment Type'), 3),
			new OptionsetField(
				'PaymentMethod',
				'',
				self::$supported_methods,
				array_shift(array_keys(self::$supported_methods))
			)
		);
		
		// If the user defined an numerically indexed array, throw an error
		if(ArrayLib::is_associative(self::$supported_methods)) {
			foreach(self::$supported_methods as $methodClass => $methodTitle) {
				
				// Create a new CompositeField with method specific fields,
				// as defined on each payment method class using getPaymentFormFields()
				$methodFields = new CompositeField(singleton($methodClass)->getPaymentFormFields());
				$methodFields->setID("MethodFields_$methodClass");
				$methodFields->addExtraClass('paymentfields');
				
				// Add those fields to the initial FieldSet we first created
				$fields->push($methodFields);
			}
		} else {
			user_error('Payment::set_supported_methods() requires an associative array.', E_USER_ERROR);
		}
		
		// Add the amount and subtotal fields for the payment amount
		$fields->push(new ReadonlyField('Amount', _t('Payment.AMOUNT', 'Amount'), $amount));
		
		return $fields;
	}
	
	/**
	 * Return the form requirements for all the payment methods.
	 * 
	 * @return An array suitable for passing to CustomRequiredFields
	 */
	static function combined_form_requirements() {
		$requirements = array();
		
		// Loop on available methods
		foreach(self::$supported_methods as $method => $methodTitle) {
			$methodRequirements = singleton($method)->getPaymentFormRequirements();
			if($methodRequirements) {
				// Put limiters into the JS/PHP code to only use those requirements for this payment method
				$methodRequirements['js'] = "for(var i=0; i <= this.elements.PaymentMethod.length-1; i++) "
					. "if(this.elements.PaymentMethod[i].value == '$method' && this.elements.PaymentMethod[i].checked == true) {"
					. $methodRequirements['js'] . " } ";

				$methodRequirements['php'] = "if(\$data['PaymentMethod'] == '$method') { " . 
					$methodRequirements['php'] . " } ";
					
				$requirements[] = $methodRequirements;
			}
		}
		
		return $requirements;
	}
	
	/**
	 * Return the payment form fields that should
	 * be shown on the checkout order form for the
	 * payment type. Example: for {@link DPSPayment},
	 * this would be a set of fields to enter your
	 * credit card details.
	 * 
	 * @return FieldSet
	 */
	function getPaymentFormFields() {
		user_error("Please implement getPaymentFormFields() on $this->class", E_USER_ERROR);
	}

	/**
	 * Define what fields defined in {@link Order->getPaymentFormFields()}
	 * should be required. 
	 * 
	 * @see DPSPayment->getPaymentFormRequirements() for an example on how
	 * this is implemented.
	 * 
	 * @return array
	 */
	function getPaymentFormRequirements() {
		user_error("Please implement getPaymentFormRequirements() on $this->class", E_USER_ERROR);
	}
	
	/**
	 * Perform payment processing for the type of
	 * payment. For example, if this was a credit card
	 * payment type, you would perform the data send
	 * off to the payment gateway on this function for
	 * your payment subclass.
	 * 
	 * This is used by {@link OrderForm} when it is
	 * submitted.
	 *
	 * @param array $data The form request data - see OrderForm
	 * @param OrderForm $form The form object submitted on
	 */
	function processPayment($data, $form) {
		user_error("Please implement processPayment() on $this->class", E_USER_ERROR);
	}
	
	function getForm($whichTest){
		user_error("Please implement getForm() on $this->class", E_USER_ERROR);
	}
	
	function payAsRecurring() {
		user_error("Please implement payAsRecurring() on $this->class", E_USER_ERROR);
	}
	
	function handleError($e){
		$this->ExceptionError = $e->getMessage();
		$this->write();
	}
	
	function PaidObject(){
		return DataObject::get_by_id($this->PaidForClass, $this->PaidForID);
	}	
}
abstract class Payment_Result {
	
	protected $value;
	
	function __construct($value = null) {
		$this->value = $value;
	}

	function getValue() {
		return $this->value;
	}

	abstract function isSuccess();
	
	abstract function isProcessing();
	
}
class Payment_Success extends Payment_Result {

	function isSuccess() {
		return true;
	}
	
	function isProcessing() {
		return false;
	}
	
}
class Payment_Processing extends Payment_Result {

	function isSuccess() {
		return false;
	}

	function isProcessing() {
		return true;
	}
	
}
class Payment_Failure extends Payment_Result {

	function isSuccess() {
		return false;
	}

	function isProcessing() {
		return false;
	}
}
?>