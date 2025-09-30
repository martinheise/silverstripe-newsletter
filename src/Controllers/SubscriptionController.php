<?php

namespace Mhe\Newsletter\Controllers;

use Mhe\Newsletter\Email\SubscriptionConfirmationEmail;
use Mhe\Newsletter\Forms\SubscriptionForm;
use Mhe\Newsletter\Model\Recipient;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;

/**
 * main controller handling subscribing and unsubscribing to newsletter channels
 */
class SubscriptionController extends Controller
{
    private static string $url_segment = 'subscription';

    private static array $allowed_actions = [
        'confirm',
        'getSubscriptionForm',
    ];

    private static array $url_handlers = [
        'SubscriptionForm' => 'getSubscriptionForm',
        'confirm//$Keys!' => 'confirm',
    ];

    public function getSubscriptionForm(): SubscriptionForm
    {
        return SubscriptionForm::create($this, 'SubscriptionForm');
    }

    public function submitSubscription($data, SubscriptionForm $form): HTTPResponse
    {
        if ($recipient = Recipient::createOrUpdateForFormData($data)) {
            $this->sendConfirmationMail($recipient);
            $form->sessionMessage(_t(__CLASS__ . '.SUBMIT_SUCCESS', 'Thank you for subscribing! Please check your email for our confirmation email.'), ValidationResult::TYPE_GOOD);
        } else {
            $form->sessionMessage(_t(__CLASS__ . '.SUBMIT_ERROR', 'Something went wrong. Please try again later'));
        }
        return $this->redirectBack();
    }

    protected function sendConfirmationMail(Recipient $recipient): void
    {
        $email = SubscriptionConfirmationEmail::create($recipient);
        $email->send();
    }

    /**
     * Action: confirm newsletter subscription, called usually by a link from the confirmation email
     *
     * @throws HTTPResponse_Exception
     */
    public function confirm(): DBHTMLText
    {
        // ToDo: set language – per additional URL key, build in original request?
        $parts = $this->getRequest()->param('Keys') ?? '';
        $parts = explode('-', $parts);
        if (count($parts) < 2 || !is_numeric($parts[0])) {
            $this->httpError(404);
        }
        // first part is the ID of the recipient
        $recipient = Recipient::get()->byID(array_shift($parts));
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
