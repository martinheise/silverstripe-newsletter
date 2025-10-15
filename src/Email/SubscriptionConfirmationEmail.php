<?php

namespace Mhe\Newsletter\Email;

use Mhe\Newsletter\Model\Recipient;
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Config\Config;

class SubscriptionConfirmationEmail extends Email
{
    public function __construct(Recipient $recipient)
    {
        $from = Config::inst()->get(self::class, 'from_email') ?? '';
        $to = $recipient->Email;
        $subject = _t(__CLASS__ . '.SUBJECT', 'Your newsletter subscription');
        parent::__construct($from, $to, $subject);
        $this->setData(['Recipient' => $recipient]);
    }
}
