<?php

namespace Mhe\Newsletter\Model;

use Mhe\Newsletter\Controllers\NewsletterAdmin;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;

/**
 * A channel is a series or category of newsletters a recipient can subscribe to
 */
class Channel extends DataObject
{
    private static $table_name = 'NLChannel';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $summary_fields = array(
        'Title'
    );

    public function canView($member = null): bool
    {
        return Permission::checkMember($member, NewsletterAdmin::CMS_ACCESS);
    }

    public function canEdit($member = null): bool
    {
        return Permission::checkMember($member, NewsletterAdmin::CMS_ACCESS);
    }

    public function canDelete($member = null): bool
    {
        return $this->canEdit($member);
    }

    public function canCreate($member = null, $context = []): bool
    {
        return $this->canEdit($member);
    }
}
