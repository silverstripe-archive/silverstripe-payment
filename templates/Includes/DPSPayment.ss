<% if TxnType=Complete %>
	<% control AuthPayment %>
		<h3>$ClassName $ID</h3>
		<ul>
			<li>Status: $Status</li>
			<li>TxnRef: $TxnRef</li>
			<li>Type: $TxnType</li>
			<li>Amount: $Amount.Amount</li>
			<li>Currency: $Amount.Currency</li>
			<li>AuthCode: $AuthCode</li>
			<li>Message: $Message</li>
			<% if PaymentDate %><li>Payment Date: $PaymentDate</li><% end_if %>
		</ul>
	<% end_control %>
<% end_if %>

<% if TxnType=Refund %>
	<% control RefundedFor %>
		<h3>$ClassName $ID</h3>
		<ul>
			<li>Status: $Status</li>
			<li>TxnRef: $TxnRef</li>
			<li>Type: $TxnType</li>
			<li>Amount: $Amount.Amount</li>
			<li>Currency: $Amount.Currency</li>
			<li>AuthCode: $AuthCode</li>
			<li>Message: $Message</li>
			<% if PaymentDate %><li>Payment Date: $PaymentDate</li><% end_if %>
		</ul>A
	<% end_control %>
<% end_if %>

<h3>$ClassName $ID</h3>
<ul>
	<li>Status: $Status</li>
	<li>TxnRef: $TxnRef</li>
	<li>Type: $TxnType</li>
	<li>Amount: $Amount.Amount</li>
	<li>Currency: $Amount.Currency</li>
	<li>AuthCode: $AuthCode</li>
	<li>Message: $Message</li>
	<% if PaymentDate %><li>Payment Date: $PaymentDate</li><% end_if %>
</ul>
