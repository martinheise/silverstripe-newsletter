<p><%t Mhe\Newsletter\Email\SubscriptionConfirmationEmail.HELLO 'Hi' %> $FullName,</p>

<p><%t Mhe\Newsletter\Email\SubscriptionConfirmationEmail.MESSAGE 'Please confirm your newsletter subscription' %></p>

<p><a href="$ConfirmationLink">$ConfirmationLink</a></p>
<ul>
    <% loop $Subscriptions %>
        <li>$Title</li>
    <% end_loop %>
</ul>
