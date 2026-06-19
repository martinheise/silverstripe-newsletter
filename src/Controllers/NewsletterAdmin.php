<?php

namespace Mhe\Newsletter\Controllers;

use Colymba\BulkManager\BulkAction\DeleteHandler;
use Colymba\BulkManager\BulkManager;
use Mhe\Newsletter\Model\Channel;
use Mhe\Newsletter\Model\Recipient;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\GridField\GridFieldConfig;

class NewsletterAdmin extends ModelAdmin
{
    public const CMS_ACCESS = 'CMS_NewsletterAdmin';

    private static string $url_segment = 'newsletter';

    private static array $managed_models = [
        Channel::class,
        Recipient::class
    ];

    private static string $required_permission_codes = self::CMS_ACCESS;

    private static string $menu_icon_class = 'font-icon-block-content';

    protected function getGridFieldConfig(): GridFieldConfig
    {
        $config = parent::getGridFieldConfig();
        // enable bulk deletion if available
        if (class_exists(BulkManager::class)) {
            $config->addComponent(BulkManager::create([], false)
                ->addBulkAction(DeleteHandler::class));
        }
        return $config;
    }
}
