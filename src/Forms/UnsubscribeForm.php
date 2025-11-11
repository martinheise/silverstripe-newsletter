<?php

namespace Mhe\Newsletter\Forms;

use Mhe\Newsletter\Model\Recipient;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\Validation\RequiredFieldsValidator;

class UnsubscribeForm extends Form
{
    public function __construct(RequestHandler $controller = null, $name = self::DEFAULT_NAME, Recipient $recipient = null, array $channelIds = [])
    {
        $fields = $this->getFormFields($recipient, $channelIds);
        $actions = FieldList::create(
            FormAction::create('submitUnsubscribe', _t(__CLASS__ . '.ACTION_submit', 'Submit'))
        );
        $validator = RequiredFieldsValidator::create('RecipientKey', 'Channels');
        parent::__construct($controller, $name, $fields, $actions, $validator);
        // After success: remove submit button and info, but keep message –ToDo: is there a nicer way?
        $validation = $this->getSessionValidationResult();
        if (isset($validation) && $validation->isValid() && count($validation->getMessages()) == 1 && $validation->getMessages()[0]['messageType'] == 'good') {
            $this->Actions()->removeByName('action_submitUnsubscribe');
            $this->Fields()->removeByName('ChannelInfo');
        }
    }

    /**
     * Get the FieldList for the form, possibly using extensions
     *
     * @param Recipient|null $recipient recipient using this form
     * @param array $channelIds optional IDs to filter the available channels
     * @return FieldList
     */
    protected function getFormFields(Recipient $recipient = null, array $channelIds = []): FieldList
    {
        $fields = FieldList::create();
        if ($recipient) {
            $subscriptions = [];
            foreach ($recipient->Subscriptions() as $subscription) {
                if (count($channelIds) == 0 || in_array($subscription->ID, $channelIds)) {
                    $subscriptions[$subscription->ID] = $subscription->Title;
                }
            }
            // literal field with note and channel names – ToDo: enable output per template
            $channels = [];
            foreach ($subscriptions as $title) {
                $channels[] .= $title;
            }
            $fields->push(
                LiteralField::create('ChannelInfo', _t(__CLASS__ . '.CHANNELINFO', '<p>Submit to unsubscribe from these channels: {channels}</p>', [ 'channels' => join(', ', $channels)]))
            );
            $fields->push(
                HiddenField::create('RecipientKey', null, $recipient->Key)
            );
            foreach ($subscriptions as $id => $subscription) {
                $fields->push(
                    HiddenField::create("Channels[$id]", null, $id)
                );
            }
        }
        $this->extend('updateFormFields', $fields);
        return $fields;
    }
}
