<?php

namespace Mhe\Newsletter\Forms\Validation;

use Mhe\Newsletter\Forms\SubscriptionForm;
use Mhe\Newsletter\Model\Recipient;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Forms\Validation\RequiredFieldsValidator;

class SubscriptionFormValidator extends RequiredFieldsValidator
{
    /**
     * regexes per form field that are considered invalid,
     * mainly to block some spam
     * @config
     */
    private static array $invalid_field_regex = [
        'FullName' => '![<>\p{S}]|https?:!u',
    ];

    public function __construct()
    {
        parent::__construct('Email', 'Channels', 'Terms');
    }

    public function php($data): bool
    {
        $valid = parent::php($data);
        $regexConfig = Config::inst()->get(self::class, 'invalid_field_regex');
        if (is_array($regexConfig)) {
            foreach ($regexConfig as $field => $regex) {
                if (isset($data[$field]) && preg_match($regex, $data[$field])) {
                    $this->validationError(
                        $field,
                        _t(
                            SubscriptionForm::class . '.INVALID_FIELD_REGEX',
                            'Invalid value for {field}',
                            ['field' => Recipient::singleton()->fieldLabel($field)]
                        ),
                        ValidationResult::TYPE_ERROR
                    );
                    $valid = false;
                }
            }
        }
        $results = $this->extend('updatePHP', $data, $this->form);
        $results[] = $valid;
        return min($results);
    }
}
