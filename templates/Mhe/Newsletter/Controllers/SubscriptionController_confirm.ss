<article>
    <h1>$Title</h1>
    <% with $Recipient %>
    <p><%t Mhe\Newsletter\Controllers\SubscriptionController.CONFIRM_Message 'Your newsletter subscription was confirmed. You have {count} active subscriptions:' count=$ActiveSubscriptions.Count %></p>
    <ul>
        <% loop $ActiveSubscriptions %>
            <li>$Title</li>
        <% end_loop %>
    </ul>
    <% end_with %>
</article>
