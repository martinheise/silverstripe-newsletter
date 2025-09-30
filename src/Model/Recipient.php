<?php

namespace Mhe\Newsletter\Model;

use Mhe\Newsletter\Controllers\SubscriptionController;
use SilverStripe\Control\Controller;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;

/**
 * A newsletter recipient is a person who subscribed to one or more channels {@see \Mhe\Newsletter\Model\Channel}
 * They should have at least a valid email address and possible additional information like their name
 *
 * @property string $FullName optional name of the recipient
 * @property string $Email Email address of the recipient
 *
 * @method ManyManyList<Channel> Subscriptions() Subscribed channels
 */
class Recipient extends DataObject implements PermissionProvider
{
    /**
     * Permission to view Recipients
     */
    public const VIEW_ALL = 'NLRecipient_VIEW_ALL';

    /**
     * Permission to edit Recipients
     */
    public const EDIT_ALL = 'NLRecipient_EDIT_ALL';

    private static $table_name = 'NLRecipient';

    private static $db = [
        'FullName' => 'Varchar',
        'Email' => 'Varchar(254)'
    ];

    private static $summary_fields = array(
        'FullName',
        'Email'
    );

    private static $many_many = [
        'Subscriptions' => Channel::class,
    ];

    private static $many_many_extraFields = [
        'Subscriptions' => [
            'ConfirmationKey' => 'Varchar(40)',
            'Confirmed' => 'Datetime'
        ]
    ];

    public function providePermissions(): array
    {
        $perms = [
            self::VIEW_ALL => [
                'name' => _t(__CLASS__ . '.VIEW_ALL_NAME', 'View newsletter recipients'),
                'category' => _t('SilverStripe\\Security\\Permission.CONTENT_CATEGORY', 'Content permissions'),
                'help' => _t(__CLASS__ . '.VIEW_ALL_HELP', 'View newsletter recipients.'),
                'sort' => 221
            ],
            self::EDIT_ALL => [
                'name' => _t(__CLASS__ . '.EDIT_ALL_NAME', 'Edit newsletter recipients'),
                'category' => _t('SilverStripe\\Security\\Permission.CONTENT_CATEGORY', 'Content permissions'),
                'help' => _t(__CLASS__ . '.EDIT_ALL_HELP', 'Manage newsletter recipients.'),
                'sort' => 222
            ]
        ];
        return $perms;
    }

    public function canView($member = null): bool
    {
        $extended = $this->extendedCan('canView', $member);
        if ($extended !== null) {
            return $extended;
        }
        return Permission::checkMember($member, self::VIEW_ALL);
    }

    public function canEdit($member = null): bool
    {
        $extended = $this->extendedCan('canEdit', $member);
        if ($extended !== null) {
            return $extended;
        }
        return Permission::checkMember($member, self::EDIT_ALL);
    }

    public function canDelete($member = null): bool
    {
        return $this->canEdit($member);
    }

    public function canCreate($member = null, $context = []): bool
    {
        return $this->canEdit($member);
    }

    public static function createOrUpdateForFormData($data = []): ?Recipient
    {
        if (empty($data['Email'])) return null;
        $recipient = Recipient::get()->filter(['Email' => $data['Email']])->first();
        $fieldValues = array_intersect_key($data, array_flip(['Email', 'FullName']));
        try {
            if (!$recipient) {
                $recipient = Recipient::create($fieldValues);
            } else {
                $recipient->update($fieldValues);
            }
            $recipient->write();
            foreach ($data['Channels'] ?? [] as $channelId) {
                if ($recipient->Subscriptions()->byID($channelId)) continue;
                $channel = Channel::get()->byID($channelId);
                if (!$channel) continue;
                $recipient->Subscriptions()->add($channel);

                // ToDo: necessary to ASSURE 100% uniqueness?
                // ToDo: expire Key?
                $recipient->Subscriptions()->setExtraData($channelId, ['ConfirmationKey' => sha1(mt_rand() . mt_rand())]);
            }
        } catch (ValidationException) {
            return null;
        }
        return ($recipient);
    }

    /**
     *  Get all active/confirmed subscriptions
     *  @return ManyManyList<Channel>
     * /ent>
     */
    public function getActiveSubscriptions(): ManyManyList
    {
        return $this->Subscriptions()->filter('Confirmed:not', null);
    }

    /**
     * confirm recipient’s subscriptions matching the given keys
     * @param array $keys
     * @return void
     */
    public function confirmSubscriptions(array $keys)
    {
        $subscriptionIds = $this->Subscriptions()->filter(['ConfirmationKey' => $keys])->column('ID');
        foreach ($subscriptionIds as $id) {
            $this->Subscriptions()->setExtraData($id, [
                'ConfirmationKey' => '',
                'Confirmed' => DBDatetime::now()
            ]);
        }
    }

    public function getConfirmationLink(): string {
        $link = SubscriptionController::singleton()->AbsoluteLink('confirm');
        $parts = [ $this->ID ];
        foreach ($this->Subscriptions() as $subscription) {
            if ($subscription->ConfirmationKey) {
                $parts[] = $subscription->ConfirmationKey;
            }
        }
        $link = Controller::join_links($link, join('-', $parts));
        return $link;
    }
}
