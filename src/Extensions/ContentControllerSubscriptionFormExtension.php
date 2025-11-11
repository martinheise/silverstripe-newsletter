<?php

namespace Mhe\Newsletter\Extensions;

use Mhe\Newsletter\Forms\SubscriptionForm;
use Mhe\Newsletter\Model\Channel;
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
     * @param ?string $channelName name of one channel to subscribe, if empty user can select from all channels
     * @param ?string $idPostfix postfix to add to the generated form HTML id, helpful for multiple forms on one page
     * @return SubscriptionForm
     */
    public function ChannelSubscriptionForm(?string $channelName = null, ?string $idPostfix = null): SubscriptionForm
    {
        $channels = [];
        if ($channelName != '') {
            $channels = [Channel::get()->filter(['Title' => $channelName])->first()];
        }
        $form = SubscriptionForm::create_default($channels);
        $form->setIdPostfix($idPostfix);
        return $form;
    }
}
