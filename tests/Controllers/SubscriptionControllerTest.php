<?php

namespace Mhe\Newsletter\Tests\Controllers;

use Mhe\Newsletter\Model\Channel;
use Mhe\Newsletter\Model\Recipient;
use Page;
use SilverStripe\Control\Director;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\View\SSViewer;

class SubscriptionControllerTest extends FunctionalTest
{
    protected static $fixture_file = 'SubscriptionControllerTest.yml';

    protected $autoFollowRedirection = true;

    protected static string $test_theme = 'test-newsletter';

    protected function setUp(): void
    {
        parent::setUp();
        DBDatetime::set_mock_now('2025-01-15T08:15:00');

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

    public function tearDown(): void
    {
        DBDatetime::clear_mock_now();
        parent::tearDown();
    }

    public function testSubmitSubscriptionInvalid()
    {
        // ToDo: with dataprovider (PHPUnit >= 10)?
        $this->get('home');
        $this->submitForm('SubscriptionForm_SubscriptionForm', 'action_submitSubscription', ['Email' => '', 'Channels[1]' => 1]);
        $this->assertPartialMatchBySelector('.message', '"Email" is required');

        $this->get('home');
        $this->submitForm('SubscriptionForm_SubscriptionForm', 'action_submitSubscription', ['Email' => 'test@example.org']);
        $this->assertPartialMatchBySelector('.message', 'At least one "Channels" is required');
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
        $sub_new = $subscriptions->first();
        $this->assertEquals($channelId, $sub_new->ID);
        $this->assertEmpty($sub_new->Confirmed);
        $this->assertMatchesRegularExpression('/[a-f0-9]{40}/', $sub_new->ConfirmationKey);

        // email was sent
        $this->assertEmailSent($email);
        $mail = $this->findEmail($email);
        $this->assertEquals('Your newsletter subscription', $mail['Subject']);
        $this->assertStringContainsString('Hi Jane Doe', $mail['Content']);
        $this->assertStringContainsString('Please confirm your newsletter subscription', $mail['Content']);
        $this->assertStringContainsString('Monthly', $mail['Content']);
        $link = Director::absoluteURL('subscription/confirm/' . $recipient->ID . '-' . $sub_new->ConfirmationKey);
        $this->assertStringContainsString($link, $mail['Content']);
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
        $this->assertEmpty($sub_old->Confirmed);
        $this->assertEquals('aaaaaaaa00000000', $sub_old->ConfirmationKey);
        $sub_old2 = $recipient->Subscriptions()->byID($this->idFromFixture(Channel::class, 'news'));
        $this->assertNotEmpty($sub_old2->Confirmed);
        $this->assertEmpty($sub_old2->ConfirmationKey);

        // email was sent
        $this->assertEmailSent($email);
        $mail = $this->findEmail($email);
        $this->assertEquals('Your newsletter subscription', $mail['Subject']);
        $this->assertStringContainsString('Hi El Duderino', $mail['Content']);
        $this->assertStringContainsString('Please confirm your newsletter subscription', $mail['Content']);
        $this->assertStringContainsString('Monthly', $mail['Content']);
        $link = Director::absoluteURL('subscription/confirm/' . $recipient->ID, '-', 'aaaaaaaa00000000' . '-' . $sub_new->ConfirmationKey);
        $this->assertStringContainsString($link, $mail['Content']);
    }

    public function testConfirmInvalid()
    {
        $response = $this->get("subscription/confirm/");
        $this->assertEquals(404, $response->getStatusCode());
        $response = $this->get("subscription/confirm/abc");
        $this->assertEquals(404, $response->getStatusCode());
        $response = $this->get("subscription/confirm/999");
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testConfirmSubscription() {
        $recipient = $this->objFromFixture(Recipient::class, 'donny');
        $this->get("subscription/confirm/{$recipient->ID}-bbbb1111-cccc2222");

        // subscriptions confirmed by URL param
        $subscription = $recipient->Subscriptions()->byID($this->idFromFixture(Channel::class, 'monthly'));
        $this->assertEquals(DBDatetime::now(), $subscription->Confirmed);
        $this->assertEmpty($subscription->ConfirmationKey);
        $subscription = $recipient->Subscriptions()->byID($this->idFromFixture(Channel::class, 'weekly'));
        $this->assertEquals(DBDatetime::now(), $subscription->Confirmed);
        $this->assertEmpty($subscription->ConfirmationKey);

        // additional subscription: not confirmed
        $subscription = $recipient->Subscriptions()->byID($this->idFromFixture(Channel::class, 'news'));
        $this->assertEmpty($subscription->Confirmed);
        $this->assertEquals('dddd3333', $subscription->ConfirmationKey);

        // check output
        $this->assertPartialMatchBySelector('p', 'Your newsletter subscription was confirmed. You have 2 active subscriptions:');
        $this->assertPartialMatchBySelector('li', ['Weekly', 'Monthly']);
    }

    public function testUnsubscribe() {
        // ToDo: unsubscribe per link
        $this->assertEquals(1, 1);
    }
}
