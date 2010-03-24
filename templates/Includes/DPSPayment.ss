<% if TxnType=Complete %>
	<% control AuthPayment %>
		<h3>$ClassName $ID</h3>
		<% if ExceptionError %>
			<div class="exception message">$ExceptionError</div>
		<% else %>
			<ul>
				<li>Status: $Status</li>
				<% if TxnRef %><li>TxnRef: $TxnRef</li><% end_if %>
				<li>Type: $TxnType</li>
				<li>Amount: $Amount.Amount</li>
				<li>Currency: $Amount.Currency</li>
				<% if AuthCode %><li>AuthCode: $AuthCode</li><% end_if %>
				<% if Message %><li>Message: $Message</li><% end_if %>
				<% if PaymentDate %><li>Payment Date: $PaymentDate</li><% end_if %>
			</ul>
		<% end_if %>
	<% end_control %>
<% end_if %>

<% if TxnType=Refund %>
	<% control RefundedFor %>
		<h3>$ClassName $ID</h3>
		<% if ExceptionError %>
			<div class="exception message">$ExceptionError</div>
		<% else %>
			<ul>
				<li>Status: $Status</li>
				<% if TxnRef %><li>TxnRef: $TxnRef</li><% end_if %>
				<li>Type: $TxnType</li>
				<li>Amount: $Amount.Amount</li>
				<li>Currency: $Amount.Currency</li>
				<% if AuthCode %><li>AuthCode: $AuthCode</li><% end_if %>
				<% if Message %><li>Message: $Message</li><% end_if %>
				<% if PaymentDate %><li>Payment Date: $PaymentDate</li><% end_if %>
			</ul>
		<% end_if %>
	<% end_control %>
<% end_if %>

<h3>$ClassName $ID</h3>
<% if ExceptionError %>
	<div class="exception message">$ExceptionError</div>
<% else %>
	<ul>
		<li>Status: $Status</li>
		<% if TxnRef %><li>TxnRef: $TxnRef</li><% end_if %>
		<li>Type: $TxnType</li>
		<li>Amount: $Amount.Amount</li>
		<li>Currency: $Amount.Currency</li>
		<% if AuthCode %><li>AuthCode: $AuthCode</li><% end_if %>
		<% if Message %><li>Message: $Message</li><% end_if %>
		<% if PaymentDate %><li>Payment Date: $PaymentDate</li><% end_if %>
	</ul>
<% end_if %>
