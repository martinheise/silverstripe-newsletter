<?php

namespace Mhe\Newsletter\Extensions;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TreeDropdownField;

class SiteConfigExtension extends Extension
{
    private static $has_one = [
        'NLTermsPage' => SiteTree::class,
    ];

    public function updateCMSFields(FieldList $fields): void
    {
        $fields->addFieldToTab(
            "Root.Newsletter",
            TreeDropdownField::create(
                'NLTermsPage',
                $this->owner->fieldLabel('NLTermsPage'),
                SiteTree::class
            )
        );
    }
}
