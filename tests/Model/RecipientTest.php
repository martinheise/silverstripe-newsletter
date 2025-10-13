<?php

namespace Mhe\Newsletter\Tests\Model;

use Mhe\Newsletter\Model\Channel;
use Mhe\Newsletter\Model\Recipient;
use SilverStripe\Control\Director;
use SilverStripe\Dev\SapphireTest;

class RecipientTest extends SapphireTest
{
    protected static $fixture_file = 'RecipientTest.yml';

    /**
     * test filter for active subscriptions
     */
    public function testActiveSubscriptions()
    {
        $recipient = $this->objFromFixture(Recipient::class, 'dude');
        $subscriptions = $recipient->getActiveSubscriptions();
        $this->assertCount(1, $subscriptions);
        $this->assertEquals('News', $subscriptions[0]->Title);
    }

    /**
     * test automatic generation of unique key after creation
     */
    public function testGenerateKey()
    {
        $recipient = Recipient::create(['FullName' => 'John Doe']);
        $recipient->write();
        $this->assertEquals('John Doe', $recipient->FullName);
        $this->assertMatchesRegularExpression('/[a-f0-9]{40}/', $recipient->Key);
    }

    /**
     * test creation of a new recipient from form data
     */
    public function testCreateForFormData()
    {
        $channelId = $this->idFromFixture(Channel::class, 'weekly');
        $recipient = Recipient::createOrUpdateForFormData([
            'Email' => 'test1@example.org',
            "FullName" => "John Doe",
            "Channels" => [$channelId]
        ]);
        $this->assertEquals('John Doe', $recipient->FullName);
        $this->assertEquals('test1@example.org', $recipient->Email);
        $this->assertMatchesRegularExpression('/[a-f0-9]{40}/', $recipient->Key);
        // Subscriptions
        $this->assertCount(1, $recipient->Subscriptions());
        $sub_new = $recipient->Subscriptions()->byID($channelId);
        $this->assertEmpty($sub_new->Confirmed);
        $this->assertMatchesRegularExpression('/[a-f0-9]{40}/', $sub_new->ConfirmationKey);
    }

    /**
     * test update of an existing recipient from form data
     */
    public function testUpdateForFormData()
    {
        $channelId = $this->idFromFixture(Channel::class, 'monthly');
        $recipient = Recipient::createOrUpdateForFormData([
            'Email' => 'dude@example.org',
            "Channels" => [$channelId]
        ]);
        $this->assertEquals('The Dude', $recipient->FullName);
        $this->assertEquals('dude@example.org', $recipient->Email);
        $this->assertEquals('abcdef0135792468', $recipient->Key);
        // Subscriptions
        $this->assertCount(3, $recipient->Subscriptions());
        $sub_new = $recipient->Subscriptions()->byID($channelId);
        $this->assertEmpty($sub_new->Confirmed);
        $this->assertMatchesRegularExpression('/[a-f0-9]{40}/', $sub_new->ConfirmationKey);
    }

    /**
     * test generation of a link for subscription confirmation
     */
    public function testConfirmationLink()
    {
        $recipient = $this->objFromFixture(Recipient::class, 'dude');
        $this->assertEquals(Director::absoluteBaseURL() . '/subscription/confirm/' . 'abcdef0135792468' . '-aaaaaaaa00000000', $recipient->getConfirmationLink());
    }

    /**
     * test generation of a link for unsubscribing all subscriptions
     */
    public function testUnsubscribeLinkAll()
    {
        $recipient = $this->objFromFixture(Recipient::class, 'dude');
        $this->assertEquals(Director::absoluteBaseURL() . '/subscription/unsubscribe/' . 'abcdef0135792468', $recipient->getUnsubscribeLink());
    }

    /**
     * test generation of links for unsubscribing a selected channel
     */
    public function testUnsubscribeLinkChannel()
    {
        $recipient = $this->objFromFixture(Recipient::class, 'dude');
        // unsubscribe from one channel
        $channel = $this->objFromFixture(Channel::class, 'news');
        $this->assertEquals(Director::absoluteBaseURL() . '/subscription/unsubscribe/' . 'abcdef0135792468' . '?ch=' . $channel->ID, $recipient->getUnsubscribeLink($channel));
        // unsubscribe from unconfirmed channel (cancel)
        $channel = $this->objFromFixture(Channel::class, 'weekly');
        $this->assertEquals(Director::absoluteBaseURL() . '/subscription/unsubscribe/' . 'abcdef0135792468' . '?ch=' . $channel->ID, $recipient->getUnsubscribeLink($channel));
        // unsubscribed channel
        $channel = $this->objFromFixture(Channel::class, 'monthly');
        $this->assertEquals("", $recipient->getUnsubscribeLink($channel));
    }
}
