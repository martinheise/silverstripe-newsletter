<?php

namespace Mhe\Newsletter\Forms;

use Mhe\Newsletter\Model\Channel;
use SilverStripe\Forms\RequiredFields;

/**
 * Validator for SubscriptionForm
 * In addition to the regular required fields it checks for at least one channel for subscription
 */
class SubscriptionValidator extends RequiredFields
{

    /**
     * @inheritDoc
     */
    public function php($data)
    {
        $valid = parent::php($data);

        $fieldName = 'Channels';
        $formField = $this->form->Fields()->dataFieldByName($fieldName);

        $value = isset($data[$fieldName]) ? $data[$fieldName] : null;

        $channel_valid = false;
        if (is_array($value)) {
            foreach ($value as $id) {
                if (Channel::get()->byID($id)) {
                    $channel_valid = true;
                    break;
                }
            }
        }
        if (!$channel_valid) {
            $this->validationError(
                $fieldName,
                _t(
                    __CLASS__ . '.CHANNEL_REQUIRED',
                    'At least one {name} is required',
                    [
                        'name' => strip_tags(
                            '"' . ($formField->Title() ?: $fieldName) . '"'
                        )
                    ]
                ),
                "required"
            );
            $valid = false;
        }
        return $valid;
    }
}
