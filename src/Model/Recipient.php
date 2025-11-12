<?php

namespace Mhe\Newsletter\Model;

use Exception;
use Mhe\Newsletter\Controllers\SubscriptionController;
use SilverStripe\Core\Validation\ValidationException;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;

/**
 * A newsletter recipient is a person who subscribed to one or more channels {@see Channel}
 * They should have at least a valid email address and possible additional information like their name
 *
 * @property string $FullName optional name of the recipient
 * @property string $Email Email address of the recipient
 * @property string $Key Auto-generated key for (un-)subscription links
 *
 * @method ManyManyList<Channel> Subscriptions() Subscribed channels
 */
class Recipient extends DataObject implements PermissionProvider
{
    /**
     * Permission to view Recipients
     */
    public const string VIEW_ALL = 'NLRecipient_VIEW_ALL';

    /**
     * Permission to edit Recipients
     */
    public const string EDIT_ALL = 'NLRecipient_EDIT_ALL';

    private static string $table_name = 'NLRecipient';

    private static array $db = [
        'FullName' => 'Varchar',
        'Email' => 'Varchar(254)',
        'Key' => 'Varchar',
    ];

    private static array $summary_fields = array(
        'FullName',
        'Email',
        'Subscriptions.Count',
        'ActiveSubscriptions.Count',
        'ActiveSubscriptions.First.Title'
    );

    private static array $searchable_fields = [
        'FullName',
        'Email',
        'Key'
        //'Subscriptions.Title' // deactivated for now because of error in Channel GridField
    ];

    private static array $many_many = [
        'Subscriptions' => Channel::class,
    ];

    private static array $many_many_extraFields = [
        'Subscriptions' => [
            'ConfirmationKey' => 'Varchar(40)',
            'Confirmed' => 'Datetime'
        ]
    ];

    /**
     * length of auto generated keys
     * @config
     */
    private static int $autokey_length = 40;

    /**
     * Characters used for auto generated keys
     * @config
     */
    private static string $autokey_chars = 'abcdef0123456789';

    public function providePermissions(): array
    {
        return [
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

    protected function onBeforeWrite(): void
    {
        parent::onBeforeWrite();
        if (!$this->Key || $this->Key == '') {
            $this->Key = $this->generateKey();
        }
    }

    /**
     * Get the matching recipient for the given key
     * @param string $key
     * @return static|null
     */
    public static function get_by_key(string $key): ?static
    {
        if (trim($key) === '') {
            return null;
        }
        return Recipient::get()->filter(['Key' => $key])->first();
    }

    protected function generateKey(): string
    {
        do {
            $key = self::randomKey();
        } while (
            // assure unique codes – force case-insensitive search (default for MySQL anyway)
            self::get()->filter('Key:nocase', $key)->exists()
        );
        return $key;
    }

    protected static function randomKey(): string
    {
        $chars = static::config()->get('autokey_chars');
        $length = static::config()->get('autokey_length');
        $key = '';
        for ($i = 0; $i < $length; $i++) {
            $key .= substr($chars, random_int(0, strlen($chars) - 1), 1);
        }
        return $key;
    }


    /**
     * @throws Exception
     */
    public static function createOrUpdateForFormData($data = []): ?Recipient
    {
        if (empty($data['Email'])) {
            return null;
        }
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
                if ($recipient->Subscriptions()->byID($channelId)) {
                    continue;
                }
                $channel = Channel::get()->byID($channelId);
                if (!$channel) {
                    continue;
                }
                $recipient->Subscriptions()->add($channel);

                // ToDo: do we need expiry dates for keys?
                // ToDo: put in seperate method, assure uniqueness
                $recipient->Subscriptions()->setExtraData($channelId, ['ConfirmationKey' => self::randomKey()]);
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
    public function confirmSubscriptions(array $keys): void
    {
        $subscriptionIds = $this->Subscriptions()->filter(['ConfirmationKey' => $keys])->column();
        foreach ($subscriptionIds as $id) {
            $this->Subscriptions()->setExtraData($id, [
                'ConfirmationKey' => '',
                'Confirmed' => DBDatetime::now()
            ]);
        }
    }

    /**
     * Get a link for confirmation of all unconfirmed subscriptions of the recipient
     * @return string
     */
    public function getConfirmationLink(): string
    {
        return SubscriptionController::singleton()->getConfirmLink($this, $this->Subscriptions());
    }

    /**
     * Get a link for
     * @param ?Channel $channel
     * @return string
     */
    public function getUnsubscribeLink(?Channel $channel = null): string
    {
        if ($channel) {
            if (($sub = $this->Subscriptions()->filter(["ID" => $channel->ID])) && $sub->exists()) {
                return SubscriptionController::singleton()->getUnsubscribeLink($this, $sub);
            } else {
                return "";
            }
        }
        return SubscriptionController::singleton()->getUnsubscribeLink($this);
    }

    public function getFrontEndFields($params = null): FieldList
    {
        $fields = parent::getFrontEndFields($params);
        $fields->removeByName('Key');
        return $fields;
    }

    public function getCMSFields(): FieldList
    {
        $fields = $this->scaffoldFormFields([
            'includeRelations' => false,
            'tabbed' => false,
            'ajaxSafe' => true
        ]);
        $fields->fieldByName('Key')->setReadonly(true);

        if ($this->ID > 0) {
            $gridConfig = GridFieldConfig_RelationEditor::create();

            $singletonChannel = singleton(Channel::class);
            // edit many_many_extraFields as detail form
            $detailFields = new FieldList(
                [
                    new ReadonlyField('Title', $singletonChannel->fieldLabel('Title')),
                    new ReadonlyField('ManyMany[ConfirmationKey]', $this->fieldLabel('ConfirmationKey')),
                    new DatetimeField('ManyMany[Confirmed]', $this->fieldLabel('Confirmed')),
                ]
            );
            // ToDo: when writing: if confirmed, delete ConfirmationKey for cleanup and consistent data?
            $detailForm = $gridConfig->getComponentByType(GridFieldDetailForm::class);
            $detailForm->setFields($detailFields);

            // modify grid columns – ToDo: localization
            $data = $gridConfig->getComponentByType(GridFieldDataColumns::class);
            $displayFields = ['Title' => 'Title', 'Confirmed' => 'Confirmed'];
            foreach ($displayFields as $key => $field) {
                $displayFields[$key] = $singletonChannel->fieldLabel($key);
            }
            $data->setDisplayFields($displayFields);

            $fields->push(
                GridField::create(
                    'Subscriptions',
                    $this->fieldLabel('Subscriptions'),
                    $this->Subscriptions(),
                    $gridConfig
                )
            );
        }
        $this->extend('updateCMSFields', $fields);
        return $fields;
    }

    /**
     * simple way adding localization to aggregated fields
     * for display in GridField
     *
     * @param bool $includerelations
     * @return array
     */
    public function fieldLabels($includerelations = true): array
    {
        $labels = parent::fieldLabels($includerelations);
        $labels['Confirmed'] = _t(__CLASS__ . '.aggr_Confirmed', 'Confirmed');
        $labels['ConfirmationKey'] = _t(__CLASS__ . '.aggr_ConfirmationKey', 'Confirmation Key');
        $labels['ActiveSubscriptions.Count'] = _t(__CLASS__ . '.aggr_ActiveSubscriptionsCount', 'First Active Subscription Title');
        $labels['ActiveSubscriptions.First.Title'] = _t(__CLASS__ . '.aggr_ActiveSubscriptionsFirstTitle', 'Active Subscriptions Count');
        $labels['Subscriptions.Count'] = _t(__CLASS__ . '.aggr_SubscriptionsCount', 'Subscriptions Count');
        return $labels;
    }
}
