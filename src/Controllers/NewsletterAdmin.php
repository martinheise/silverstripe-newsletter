<?php

namespace Mhe\Newsletter\Controllers;

use Mhe\Newsletter\Model\Channel;
use Mhe\Newsletter\Model\Recipient;
use SilverStripe\Admin\ModelAdmin;

class NewsletterAdmin extends ModelAdmin
{
    public const CMS_ACCESS = 'CMS_NewsletterAdmin';

    private static $url_segment = 'dlcodes';

    private static $managed_models = [
        'channels' => ['title' => "Channels", 'dataClass' => Channel::class],
        'recipients' => ['title' => "Recipients", 'dataClass' => Recipient::class]
    ];

    private static $required_permission_codes = self::CMS_ACCESS;

    private static $menu_icon_class = 'font-icon-mail';
}
