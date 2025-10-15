<html>
<head>
    <% base_tag %>
</head>
<body>
<p><%t Mhe\Newsletter\Email\SubscriptionConfirmationEmail.HELLO 'Hi' %> $Recipient.FullName,</p>
<p><%t Mhe\Newsletter\Email\SubscriptionConfirmationEmail.MESSAGE 'Please confirm your newsletter subscription' %></p>
<p><a href="$Recipient.ConfirmationLink">$Recipient.ConfirmationLink</a></p>
<ul>
    <% loop $Recipient.Subscriptions %>
        <li>$Title</li>
    <% end_loop %>
</ul>
</body>
</html>
