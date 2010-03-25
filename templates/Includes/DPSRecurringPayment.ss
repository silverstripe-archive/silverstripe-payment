<h3>$ClassName $ID</h3>
<% if ExceptionError %>
	<div class="exception message">$ExceptionError</div>
<% else %>
<ul>
	<li>Status: $Status</li>
	<% if TxnRef %><li>TxnRef: $TxnRef</li><% end_if %>
	<li>Amount: $Amount.Amount</li>
	<li>Currency: $Amount.Currency</li>
	<% if DPSBillingID %><li>DPSBillingID: $DPSBillingID</li><% end_if %>
	<% if AuthCode %><li>AuthCode: $AuthCode</li><% end_if %>
	<% if Message %><li>Message: $Message</li><% end_if %>
	<% if PaymentDate %><li>Payment Date: $PaymentDate</li><% end_if %>
</ul>
<% end_if %>

<% if CanPayNext %>
	<br />
	<a href="$HarnessPayNextLink"> -- Trigger Next</a>
<% end_if %>
<% if Payments %>
	<% control Payments %>
		<% include DPSPayment %>
	<% end_control %>
<% end_if %>