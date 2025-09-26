<?php

namespace Mhe\Newsletter\Forms;

use Mhe\Newsletter\Model\Recipient;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\Validation\RequiredFieldsValidator;
use SilverStripe\ORM\ValidationResult;


/*
 ToDo:
    - insert form by shortcode
    - insert form by Elements
 */

/*
 * A frontend form to subscribe to one more newsletter channels
 */
class SubscriptionForm extends Form
{
    public function __construct(RequestHandler $controller = null, $name = self::DEFAULT_NAME, array $channelNames = [])
    {
        $fields = $this->getFormFields();

        // ToDo: add channel selection if necessary

        $actions = FieldList::create(
            FormAction::create('submitSubscription', _t(__CLASS__ . '.ACTION_submit', 'Submit'))
        );
        // change in Silverstripe 6:
        if (class_exists(RequiredFieldsValidator::class)) {
            $validator = RequiredFieldsValidator::create('Email');
        } else {
            $validator = RequiredFields::create('Email');
        }
        parent::__construct($controller, $name, $fields, $actions, $validator);
    }


    /**
     * Get the FieldList for the form, possibly using extensions
     *
     * @return FieldList
     */
    protected function getFormFields(): FieldList
    {
        $fields = Recipient::singleton()->getFrontEndFields();
        $this->extend('updateFormFields', $fields);
        return $fields;
    }


    public function submit($data): ValidationResult
    {
        // ToDo: implement subscription
        $this->sessionMessage('SubscriptionForm ' . $data['FullName'] . " "  . $data['Email'], 'success');
        return $this->getSessionValidationResult();
    }
}
