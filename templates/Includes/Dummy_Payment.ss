<% if Status=Complete %>
	<ul>
		<li>Status: $Status</li>
		<% if TxnRef %><li>TxnRef: $TxnRef</li><% end_if %>
		<li>Type: $TxnType</li>
		<li>Amount: $Amount.Amount</li>
		<li>Currency: $Amount.Currency</li>
		<% if Message %><li>Message: $Message</li><% end_if %>
		<% if PaymentDate %><li>Payment Date: $PaymentDate</li><% end_if %>
	</ul>
<% end_if %>