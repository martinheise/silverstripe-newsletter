<?php

namespace Mhe\Newsletter\Extensions;

use Mhe\Newsletter\Controllers\SubscriptionController;
use Mhe\Newsletter\Forms\SubscriptionForm;
use SilverStripe\Core\Extension;
use SilverStripe\CMS\Controllers\ContentController;

/**
 * Extend ContentController with the option to add a newsletter subscription form
 *
 * @extends Extension<ContentController>
 */
class ContentControllerSubscriptionFormExtension extends Extension
{
    /**
     * Get subscription form for insertion in templates
     *
     * @param string $channelName name of one channel to subscripe, if empty user can select from all channels
     * @return SubscriptionForm
     */
    public function ChannelSubscriptionForm(string $channelName = null): SubscriptionForm
    {
        return SubscriptionForm::create(SubscriptionController::create(), 'SubscriptionForm', $channelName != '' ? [$channelName] : []);
    }
}
