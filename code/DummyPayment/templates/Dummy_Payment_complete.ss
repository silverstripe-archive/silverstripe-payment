<h3>Thank you for your payment (Ref no. #$ID)</h3>
<br />
<table>
	<tbody>
		<tr><td>Status:</td><td>$Status</td></tr>
		<% if TxnRef %><tr><td>DPS Reference:</td><td>$TxnRef</td></tr><% end_if %>
		<tr><td>Paid Amount:</td><td>$Amount.Nice ($Amount.Currency)</td></tr>
		<% if PaymentDate %><tr><td>Payment Date:</td><td>$PaymentDate.Nice</td></tr><% end_if %>
	</tbody>
</table>
