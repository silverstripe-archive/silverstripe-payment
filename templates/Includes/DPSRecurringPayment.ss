<h3>$ClassName $ID</h3>
<ul>
	<li>Status: $Status</li>
	<li>TxnRef: $TxnRef</li>
	<li>Amount: $Amount.Amount</li>
	<li>Currency: $Amount.Currency</li>
	<li>DPSBillingID: $DPSBillingID</li>
	<li>AuthCode: $AuthCode</li>
	<li>Message: $Message</li>
</ul>

<% if CanPayNext %>
	<a href="$PayNextLink">Trigger Next</a>
<% end_if %>
<% if SuccessPayments %>
	<% control SuccessPayments %>
		<% include DPSPayment %>
	<% end_control %>
<% end_if %>