<?php

namespace Mhe\Newsletter\Extensions;

use Mhe\Newsletter\Forms\SubscriptionForm;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Extension;
use SilverStripe\CMS\Controllers\ContentController;

/**
 * Extend ContentController with the option to add and handle a newsletter subscription form
 *
 * @extends Extension<ContentController>
 */
class SubscriptionFormExtension extends Extension
{
    private static $allowed_actions = [
        'getSubscriptionForm',
    ];

    private static $url_handlers = [
        'SubscriptionForm' => 'getSubscriptionForm',
    ];

    public function getChannelSubscriptionForm(array $channelNames = []): SubscriptionForm
    {
        return SubscriptionForm::create($this->owner, 'SubscriptionForm', $channelNames);
    }

    public function getSubscriptionForm(): SubscriptionForm
    {
        // ToDo: check if at least one channel exists
        return SubscriptionForm::create($this->owner, 'SubscriptionForm');
    }

    public function submitSubscription($data, SubscriptionForm $form): HTTPResponse
    {
        $result = $form->submit($data);
        // ToDo: result to handle?
        return $this ->owner->redirectBack();
    }
}
