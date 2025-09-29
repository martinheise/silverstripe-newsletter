<?php

namespace Mhe\Newsletter\Tests\Controllers;

use Mhe\Newsletter\Model\Channel;
use Mhe\Newsletter\Model\Recipient;
use Page;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\View\SSViewer;

class SubscriptionControllerTest extends FunctionalTest
{
    protected static $fixture_file = 'SubscriptionControllerTest.yml';

    protected $autoFollowRedirection = true;

    protected static string $test_theme = 'test-newsletter';

    protected function setUp(): void
    {
        parent::setUp();

        // setup test theme
        $themeBaseDir = realpath(__DIR__ . '/..');
        if (str_starts_with($themeBaseDir, BASE_PATH)) {
            $themeBaseDir = substr($themeBaseDir, strlen(BASE_PATH));
        }
        SSViewer::config()->set('theme_enabled', true);
        SSViewer::set_themes([$themeBaseDir . '/themes/' . self::$test_theme, '$default']);

        /** @var Page $page */
        foreach (Page::get() as $page) {
            $page->publishSingle();
        }
    }

    public function testSubmitSubscriptionInvalid()
    {
        // ToDo: with dataprovider (PHPUnit >= 10)?
        $this->get('home');
        $this->submitForm('SubscriptionForm_SubscriptionForm', 'action_submitSubscription', array('Email' => ''));
        $this->assertPartialMatchBySelector('.message', '"Email" is required');

        $this->get('home');
        $this->submitForm('SubscriptionForm_SubscriptionForm', 'action_submitSubscription', array('Email' => 'test@example.org'));
        $this->assertPartialMatchBySelector('.message', 'Please select at least one channel to subscribe to');
    }

    public function testSubmitSubscriptionValidNew()
    {
        $channelId = $this->idFromFixture(Channel::class, 'monthly');
        $email = 'test@example.org';
        $name = 'Jane Doe';

        $this->get('home');
        $response = $this->submitForm('SubscriptionForm_SubscriptionForm', 'action_submitSubscription', array('Email' => $email, 'FullName' => $name, "Channels[{$channelId}]" => $channelId));
        // redirected to original page
        $this->assertEquals('home', $this->mainSession->lastUrl());
        // success message
        $this->assertPartialMatchBySelector('.message', 'Thank you for subscribing! Please check your email for our confirmation email.');
        // result stored to database
        /* @var Recipient $recipient */
        $recipient = Recipient::get()->filter(['Email' => $email])->first();
        $this->assertNotNull($recipient);
        $this->assertEquals($recipient->Email, $email);
        $this->assertEquals($recipient->FullName, $name);

        $subscriptions = $recipient->Subscriptions();
        $this->assertCount(1, $subscriptions);
        $newsub = $subscriptions->first();
        $this->assertEquals($channelId, $newsub->ID);
        $this->assertEmpty($newsub->Confirmed);
        $this->assertMatchesRegularExpression('/[a-f0-9]{40}/', $newsub->ConfirmationKey);

        // ToDo: test send mail
    }

    public function testSubmitSubscriptionValidUpdate()
    {
        $channelId_new = $this->idFromFixture(Channel::class, 'monthly');
        $channelId_old = $this->idFromFixture(Channel::class, 'weekly');
        $email = 'dude@example.org';
        $name = 'El Duderino';

        $this->get('home');
        $response = $this->submitForm('SubscriptionForm_SubscriptionForm', 'action_submitSubscription', ['Email' => $email, 'FullName' => $name, "Channels[{$channelId_new}]" => $channelId_new, "Channels[{$channelId_old}]" => $channelId_old]);
        // redirected to original page
        $this->assertEquals('home', $this->mainSession->lastUrl());
        // success message
        $this->assertPartialMatchBySelector('.message', 'Thank you for subscribing! Please check your email for our confirmation email.');
        // result stored to database
        /* @var Recipient $recipient */
        $recipient = Recipient::get()->filter(['Email' => $email])->first();
        $this->assertNotNull($recipient);
        $this->assertEquals($recipient->Email, $email);
        $this->assertEquals($recipient->FullName, $name);

        $this->assertCount(3, $recipient->Subscriptions());
        $sub_new = $recipient->Subscriptions()->byID($channelId_new);
        $this->assertEmpty($sub_new->Confirmed);
        $this->assertMatchesRegularExpression('/[a-f0-9]{40}/', $sub_new->ConfirmationKey);

        // existing subscriptions were not touched
        $sub_old = $recipient->Subscriptions()->byID($channelId_old);
        $this->assertNotEmpty($sub_old->Confirmed);
        $this->assertEmpty($sub_old->ConfirmationKey);
        $sub_old2 = $recipient->Subscriptions()->byID($this->idFromFixture(Channel::class, 'news'));
        $this->assertNotEmpty($sub_old2->Confirmed);
        $this->assertEmpty($sub_old2->ConfirmationKey);

        // ToDo: test send mail
    }

    public function testConfirmSubscription() {
        // ToDo: confirm subscription per link
    }

    public function testUnsubscribe() {
        // ToDo: unsubscribe per link
    }
}
