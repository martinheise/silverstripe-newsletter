<?php

namespace Mhe\Newsletter\Forms;

use InvalidArgumentException;
use Mhe\Newsletter\Controllers\SubscriptionController;
use Mhe\Newsletter\Model\Channel;
use Mhe\Newsletter\Model\Recipient;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\Validation\RequiredFieldsValidator;
use SilverStripe\SiteConfig\SiteConfig;

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

    /**
     * @param RequestHandler|null $controller defaults to SubscriptionController
     * @param string $name
     * @param Channel[] $channels optional selection of channels, narrow down the options
     */
    public function __construct(?RequestHandler $controller = null, $name = self::DEFAULT_NAME, array $channels = [])
    {
        if (!$controller) {
            $controller = SubscriptionController::create();
        }
        $fields = $this->getFormFields($channels);
        $actions = FieldList::create(
            FormAction::create('submitSubscription', _t(__CLASS__ . '.ACTION_submit', 'Submit'))
        );
        $validator = RequiredFieldsValidator::create('Email', 'Channels', 'Terms');
        parent::__construct($controller, $name, $fields, $actions, $validator);
    }

    /**
     * convenience method: create from with default settings
     * @param Channel[] $channels optional selection of channels, narrow down the options
     * @return SubscriptionForm
     */
    public static function create_default(array $channels = []): static
    {
        return static::create(SubscriptionController::create(), "SubscriptionForm", $channels);
    }

    /**
     * Get the FieldList for the form, possibly using extensions
     *
     * @param Channel[] $channels optional list of channels
     * @return FieldList
     */
    protected function getFormFields(array $channels = []): FieldList
    {
        $fields = Recipient::singleton()->getFrontEndFields();

        if (count($channels) == 0) {
            $channels = Channel::get();
        } else {
            foreach ($channels as $channel) {
                if (!$channel instanceof Channel) {
                    throw new InvalidArgumentException('given channel options have to be objects of class Channel');
                }
            }
        }
        $sources = [];
        foreach ($channels as $channel) {
            $sources[$channel->ID] = $channel->getTitle();
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

        // mandatory: agree to terms
        $siteconfig = SiteConfig::current_site_config();
        $linkargs = [
            'termsurl' => $siteconfig->NLTermsPage ? $siteconfig->NLTermsPage->Link() : "",
            'termstitle' => $siteconfig->NLTermsPage ? $siteconfig->NLTermsPage->getMenuTitle() : _t(__CLASS__ . '.Terms', 'Terms'),
        ];
        $fields->push(
            CheckboxField::create('Terms', _t(__CLASS__ . '.TERMS_Label', 'I have understood the terms'))
                ->setCustomValidationMessage(_t(__CLASS__ . '.TERMS_Validation_Message', 'To subscribe please accept our terms.'))
                ->setDescription(_t(__CLASS__ . '.TERMS_Text', '{termstitle}: {termsurl}', $linkargs))
        );

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

    public function getIdPostfix(): ?string
    {
        return $this->idPostfix;
    }

    public function setIdPostfix(?string $idPostfix): void
    {
        $this->idPostfix = $idPostfix;
    }
}
