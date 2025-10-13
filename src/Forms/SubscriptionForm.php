<?php

namespace Mhe\Newsletter\Forms;

use Mhe\Newsletter\Model\Channel;
use Mhe\Newsletter\Model\Recipient;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\RequiredFields;

/*
 ToDo:
    - insert form by shortcode (?)
    - insert form by Elements
 */

/*
 * A frontend form to subscribe to one more newsletter channels
 */
class SubscriptionForm extends Form
{
    protected ?string $idPostfix = null;

    public function __construct(RequestHandler $controller = null, $name = self::DEFAULT_NAME, array $channelNames = [], ?string $idPostfix = null)
    {
        $fields = $this->getFormFields($channelNames);
        $actions = FieldList::create(
            FormAction::create('submitSubscription', _t(__CLASS__ . '.ACTION_submit', 'Submit'))
        );
        $validator = RequiredFields::create('Email', 'Channels');
        $this->idPostfix = $idPostfix;
        parent::__construct($controller, $name, $fields, $actions, $validator);
    }

    /**
     * Get the FieldList for the form, possibly using extensions
     *
     * @param array $channelNames optional names to filter the available channels
     * @return FieldList
     */
    protected function getFormFields(array $channelNames = []): FieldList
    {
        $fields = Recipient::singleton()->getFrontEndFields();

        $sources = [];
        /* @var Channel $channel */
        foreach (Channel::get() as $channel) {
            $sources[$channel->ID] = $channel->getTitle();
        }
        if (!empty($channelNames)) {
            $sources = array_filter($sources, fn($source) => in_array($source, $channelNames));
        }
        // checkboxes for channel selection, or hidden field if no selection is necessary
        if (count($sources) > 1) {
            $fields->push(
                CheckboxSetField::create(
                    'Channels',
                    _t(__CLASS__ . '.CHANNEL', 'Channels'),
                    $sources
                )
            );
        } elseif (count($sources) == 1) {
            $value = array_keys($sources)[0];
            $fields->push(
                $channel = HiddenField::create('Channels[' . $value . ']')
            );
            $channel->setValue($value);
        }
        $this->extend('updateFormFields', $fields);
        return $fields;
    }

    protected function getDefaultAttributes(): array
    {
        $attrs = parent::getDefaultAttributes();
        if ($this->idPostfix) {
            $attrs['id'] = $attrs['id'] . $this->idPostfix;
        }
        return $attrs;
    }
}
