<?php

namespace Mhe\Newsletter\Model;

use Mhe\Newsletter\Controllers\NewsletterAdmin;
use Mhe\Newsletter\Forms\GridFieldEnhancedExportButton;
use Mhe\Newsletter\Forms\GridFieldFixedFilter;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\TextField;
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
    private static string $table_name = 'NLChannel';

    private static array $db = [
        'Title' => 'Varchar'
    ];

    private static array $belongs_many_many = [
        'Subscribers' => Recipient::class,
    ];

    private static array $summary_fields = [
        'Title',
        'ActiveSubscribers.Count',
        'Subscribers.Count',
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
     * Get all active/confirmed subscribers
     * @return ManyManyList<Recipient>
     */
    public function getActiveSubscribers(): ManyManyList
    {
        return $this->Subscribers()->filter('Confirmed:not', null);
    }

    public function getCMSFields(): FieldList
    {
        $fields = $this->scaffoldFormFields([
            'includeRelations' => false,
            'tabbed' => false,
            'ajaxSafe' => true
        ]);

        if ($this->ID > 0) {
            $gridConfig = GridFieldConfig_RelationEditor::create();
            // only displaying / exporting confirmed recipients
            $gridConfig->addComponent(new GridFieldFixedFilter(['Confirmed:not' => null]), GridFieldPageCount::class);
            // enable export for related recipients
            $gridConfig->addComponent(
                $export = new GridFieldEnhancedExportButton("before", ['FullName', 'Email', 'Confirmed'])
            );
            $export->setExportNamePrefix($this->Title . "_");

            // edit many_many_extraFields as detail form – ToDo: localization
            $detailFields = new FieldList(
                [
                    new TextField('FullName'),
                    new EmailField('Email'),
                    new DatetimeField('ManyMany[Confirmed]')
                ]
            );
            $detailForm = $gridConfig->getComponentByType(GridFieldDetailForm::class);
            $detailForm->setFields($detailFields);

            // modify grid columns – ToDo: localization
            $data = $gridConfig->getComponentByType(GridFieldDataColumns::class);
            $displayFields = ['FullName' => 'FullName', 'Email' => 'Email', 'Confirmed' => 'Confirmed'];
            $singleton = singleton(Recipient::class);
            foreach ($displayFields as $key => $field) {
                $displayFields[$key] = $singleton->fieldLabel($key);
            }
            $data->setDisplayFields($displayFields);

            $fields->push(
                GridField::create(
                    'Subscribers',
                    $this->fieldLabel('ActiveSubscribers'),
                    $this->Subscribers(),
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
     * @param $includerelations
     * @return array
     */
    public function fieldLabels($includerelations = true)
    {
        $labels = parent::fieldLabels($includerelations);
        $labels['ActiveSubscribers'] = _t(__CLASS__ . '.aggr_ActiveSubscribers', 'Active Subscribers');
        $labels['Subscribers.Count'] = _t(__CLASS__ . '.aggr_SubscribersCount', 'Subscribers Count');
        $labels['ActiveSubscribers.Count'] = _t(__CLASS__ . '.aggr_ActiveSubscribersCount', 'Active Subscribers Count');
        return $labels;
    }
}
