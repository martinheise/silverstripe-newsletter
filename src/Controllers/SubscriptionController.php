<?php

namespace Mhe\Newsletter\Controllers;

use Mhe\Newsletter\Email\SubscriptionConfirmationEmail;
use Mhe\Newsletter\Forms\SubscriptionForm;
use Mhe\Newsletter\Model\Recipient;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;

class SubscriptionController extends Controller
{
    private static $url_segment = 'subscription';

    private static $allowed_actions = [
        'confirm',
        'getSubscriptionForm',
    ];


    private static $url_handlers = [
        'SubscriptionForm' => 'getSubscriptionForm',
        'confirm//$Keys!' => 'confirm',
    ];

    public function getSubscriptionForm(): SubscriptionForm
    {
        // ToDo: check if at least one channel exists?
        return SubscriptionForm::create($this->owner, 'SubscriptionForm');
    }

    public function submitSubscription($data, SubscriptionForm $form): HTTPResponse
    {
        if ($recipient = Recipient::createOrUpdateForFormData($data)) {
            $this->sendConfirmationMail($recipient);
            $form->sessionMessage(_t(__CLASS__ . '.SUBMIT_SUCCESS', 'Thank you for subscribing! Please check your email for our confirmation email.'), ValidationResult::TYPE_GOOD);
        } else {
            $form->sessionMessage(_t(__CLASS__ . '.SUBMIT_ERROR', 'Something went wrong. Please try again later'), ValidationResult::TYPE_ERROR);
        }
        return $this->owner->redirectBack();
    }

    protected function sendConfirmationMail(Recipient $recipient): void {
        $email = SubscriptionConfirmationEmail::create($recipient);
        $email->send();
    }

    public function confirm(): DBHTMLText
    {
        // ToDo: set language – per additional URL key, build in original request?
        $parts = $this->getRequest()->param('Keys') ?? '';
        $parts = explode('-', $parts);
        if (count($parts) < 2) {
            $this->httpError(404);
        }
        // first part is the ID of the recipient
        if (is_numeric($parts[0])) {
            $recipient = Recipient::get()->byID(array_shift($parts));
        }
        if (!$recipient) {
            $this->httpError(404);
        }
        // followings parts are the ConfirmationKeys
        $recipient->confirmSubscriptions($parts);
        // render as default Page with standard template
        return $this->customise([
            'Layout' => $this->customise($recipient)->renderWith($this->getViewer('confirm')),
        ])->renderWith(['Page']);
    }

    public function unsubscribe()
    {
        //ToDo: implement
    }
}
