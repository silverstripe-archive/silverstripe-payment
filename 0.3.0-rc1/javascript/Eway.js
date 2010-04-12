Behaviour.register({
	'#Eway_CreditCardType select' : {
		initialise : function() {hideShowCVN(this);},
		onchange : function() {hideShowCVN(this);}
	}
});

function hideShowCVN(element) {
	var display = 'none';
	if(isCvnCreditCard(element.value)) display = 'block';
	$('Eway_CreditCardCVN').style.display = display;
}

function isCvnCreditCard(type) {
	return type == 'VISA' || type == 'MASTERCARD' || type == 'AMEX';	
}