<?php

namespace Mhe\Newsletter\Controllers;

use Mhe\Newsletter\Forms\SubscriptionForm;
use Mhe\Newsletter\Model\Recipient;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;

class SubscriptionController extends Controller
{
    private static $url_segment = 'subscription';

    private static $allowed_actions = [
        'getSubscriptionForm',
    ];

    private static $url_handlers = [
        'SubscriptionForm' => 'getSubscriptionForm',
    ];

    public function getSubscriptionForm(): SubscriptionForm
    {
        // ToDo: check if at least one channel exists?
        return SubscriptionForm::create($this->owner, 'SubscriptionForm');
    }

    public function submitSubscription($data, SubscriptionForm $form): HTTPResponse
    {
        if (Recipient::createOrUpdateForFormData($data)) {
            $form->sessionMessage('Thank you for subscribing! Please check your email for our confirmation email.', ValidationResult::TYPE_GOOD);
        } else {
            $form->sessionMessage('Something went wrong. Please try again later', ValidationResult::TYPE_ERROR);
        }
        return $this->owner->redirectBack();
    }
}
