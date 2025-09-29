<?php

namespace Mhe\Newsletter\Model;

use Mhe\Newsletter\Controllers\NewsletterAdmin;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\Security\Permission;

/**
 * A channel is a series or category of newsletters a recipient can subscribe to
 *
 * @property string $Title Display name of the channel
 *
 * @method ManyManyList<Recipient> Subscribers() Subscribers to the channel
 */
class Channel extends DataObject
{
    private static $table_name = 'NLChannel';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $summary_fields = [
        'Title'
    ];

    private static $belongs_many_many = [
        'Subscribers' => Recipient::class,
    ];

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

    /**
     * Get a list of active and confirmed subscribers
     * @return Recipient[]
     */
    public function getActiveSubscribers(): array
    {
        // ToDo: implement
        return [];
    }
}
