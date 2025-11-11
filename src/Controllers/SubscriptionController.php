<?php

namespace Mhe\Newsletter\Controllers;

use Mhe\Newsletter\Email\SubscriptionConfirmationEmail;
use Mhe\Newsletter\Forms\SubscriptionForm;
use Mhe\Newsletter\Forms\UnsubscribeForm;
use Mhe\Newsletter\Model\Recipient;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Model\List\SS_List;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\ORM\FieldType\DBHTMLText;
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
        'unsubscribe',
        'getSubscriptionForm',
        'getUnsubscribeForm',
    ];

    private static array $url_handlers = [
        'SubscriptionForm' => 'getSubscriptionForm',
        'UnsubscribeForm' => 'getUnsubscribeForm',
        'confirm//$Keys!' => 'confirm',
        'unsubscribe//$Keys!' => 'unsubscribe',
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

    public function getUnsubscribeForm(): UnsubscribeForm
    {
        return UnsubscribeForm::create($this, 'UnsubscribeForm');
    }

    public function submitUnsubscribe($data, UnsubscribeForm $form): HTTPResponse
    {
        $recipient = Recipient::get_by_key($data['RecipientKey'] ?? '');
        if (!$recipient) {
            $form->sessionMessage(_t(__CLASS__ . '.UNSUBSCRIBE_ERROR', 'Could not perform unsubscribing.'));
            return $this->redirectBack();
        }
        foreach ($recipient->Subscriptions() as $subscription) {
            if (in_array($subscription->ID, $data['Channels'] ?? [])) {
                $recipient->Subscriptions()->remove($subscription);
            }
        }
        $form->sessionMessage(_t(__CLASS__ . '.UNSUBSCRIBE_SUCCESS', 'Your subscriptions have been cancelled.'), ValidationResult::TYPE_GOOD);
        return $this->redirectBack();
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
        if (count($parts) < 2 || $parts[0] == '') {
            $this->httpError(404);
        }
        // first part is the Key of the recipient
        $recipient = Recipient::get_by_key(array_shift($parts));
        if (!$recipient) {
            $this->httpError(404);
        }
        // followings parts are the ConfirmationKeys
        $recipient->confirmSubscriptions($parts);
        // render as default Page with standard template
        return $this->customise([
            'Layout' => $this->customise(['Recipient' => $recipient])->renderWith($this->getViewer('confirm')),
        ])->renderWith(['Page']);
    }

    /**
     * create a complete confirmation Link for given recipient and channel subscriptions
     * @param Recipient $recipient
     * @param SS_List|null $subscriptions
     * @return string
     */
    public function getConfirmLink(Recipient $recipient, SS_List $subscriptions = null): string
    {
        if (!$recipient->Key || $recipient->Key == '') {
            return "";
        }
        $link = $this->AbsoluteLink('confirm');
        if (!$subscriptions) {
            $subscriptions = $recipient->Subscriptions();
        }
        $parts = [ $recipient->Key ];
        foreach ($subscriptions as $subscription) {
            if ($subscription->ConfirmationKey != '') {
                $parts[] = $subscription->ConfirmationKey;
            }
        }
        return Controller::join_links($link, join('-', $parts));
    }

    public function unsubscribe(): DBHTMLText
    {
        // ToDo: set language
        $key = $this->getRequest()->param('Keys') ?? '';
        $recipient = Recipient::get_by_key($key);
        if (!$recipient) {
            $this->httpError(404);
        }
        $channelIds = $this->getRequest()->requestVar('ch') ?? [];
        if (!is_array($channelIds)) {
            $channelIds = explode(',', $channelIds);
        }
        $form = UnsubscribeForm::create($this, 'UnsubscribeForm', $recipient, $channelIds);
        return $this->customise([
            'Layout' => $this
                ->customise(['Recipient' => $recipient, 'UnsubscribeForm' => $form])
                ->renderWith($this->getViewer('unsubscribe')),
        ])->renderWith(['Page']);
    }

    /**
     * create a complete unsubscribe Link for given recipient and channel subscriptions
     * @param Recipient $recipient
     * @param SS_List|null $subscriptions
     * @return string
     */
    public function getUnsubscribeLink(Recipient $recipient, SS_List $subscriptions = null): string
    {
        if (!$recipient->Key || $recipient->Key == '') {
            return "";
        }
        $link = $this->AbsoluteLink('unsubscribe');
        $attr = "";
        if ($subscriptions && $subscriptions->count() > 0) {
            $attr = "?ch=" . join(',', $subscriptions->column('ID'));
        }
        return Controller::join_links($link, $recipient->Key, $attr);
    }

    /**
     * generate general title for use in templates
     * @return string
     */
    public function getTitle(): string
    {
        return _t(__CLASS__ . '.Title', 'Newsletter');
    }

    /**
     * enable the use of default menu from this controller
     * @param int $level Menu level to return - only level 1 will probably return something in this context
     * @return ArrayList<SiteTree>
     */
    public function getMenu(int $level = 1): ArrayList
    {
        return ContentController::singleton()->getMenu($level);
    }
}
