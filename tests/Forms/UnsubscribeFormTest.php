<?php

namespace Mhe\Newsletter\Tests\Forms;

use Mhe\Newsletter\Model\Channel;
use Mhe\Newsletter\Model\Recipient;
use Mhe\Newsletter\Test\ThemedTest;

class UnsubscribeFormTest extends ThemedTest
{
    protected static $fixture_file = 'UnsubscribeFormTest.yml';

    protected $autoFollowRedirection = true;

    /*
     * The standard form has all expected fields, including hidden fields for all channels
     */
    public function testUnsubscribeFormStandardHasAllFields(): void
    {
        $recipient = $this->objFromFixture(Recipient::class, 'dude');
        $this->get('subscription/unsubscribe/abcdef0135792468');
        $form = $this->cssParser()->getBySelector('form#UnsubscribeForm_UnsubscribeForm')[0];

        $keyfield = $form->xpath('.//input[@name="RecipientKey"]')[0] ?? null;
        $this->assertIsObject($keyfield);
        $this->assertEquals("hidden", $keyfield['type']);
        $this->assertEquals($recipient->Key, $keyfield['value']);

        $channelSelect = $form->xpath('.//input[@name="Channels[' . $this->idFromFixture(Channel::class, 'news') . ']"]')[0] ?? null;
        $this->assertIsObject($channelSelect);
        $this->assertEquals("hidden", $channelSelect['type']);
        $this->assertEquals((int)$channelSelect['value'], $this->idFromFixture(Channel::class, 'news'));

        $channelSelect = $form->xpath('.//input[@name="Channels[' . $this->idFromFixture(Channel::class, 'weekly') . ']"]')[0] ?? null;
        $this->assertIsObject($channelSelect);
        $this->assertEquals("hidden", $channelSelect['type']);
        $this->assertEquals((int)$channelSelect['value'], $this->idFromFixture(Channel::class, 'weekly'));

        $this->assertEmpty($form->xpath('.//input[@name="Channels[' . $this->idFromFixture(Channel::class, 'monthly') . ']"]'));

        $this->assertPartialMatchBySelector('p', 'Submit to unsubscribe from these channels: News, Weekly');
    }

    /*
     * The filtered form has all expected fields, including hidden fields for selected channels
     */
    public function testUnsubscribeFormFilteredHasAllFields(): void
    {
        $recipient = $this->objFromFixture(Recipient::class, 'dude');
        $channelId = $this->idFromFixture(Channel::class, 'news');

        $this->get('subscription/unsubscribe/abcdef0135792468?ch=' . $channelId);
        $form = $this->cssParser()->getBySelector('form#UnsubscribeForm_UnsubscribeForm')[0];

        $keyfield = $form->xpath('.//input[@name="RecipientKey"]')[0] ?? null;
        $this->assertIsObject($keyfield);
        $this->assertEquals("hidden", $keyfield['type']);
        $this->assertEquals($recipient->Key, $keyfield['value']);

        $channelSelect = $form->xpath('.//input[@name="Channels[' . $channelId . ']"]')[0] ?? null;
        $this->assertIsObject($channelSelect);
        $this->assertEquals("hidden", $channelSelect['type']);
        $this->assertEquals((int)$channelSelect['value'], $channelId);

        $this->assertEmpty($form->xpath('.//input[@name="Channels[' . $this->idFromFixture(Channel::class, 'weekly') . ']"]'));
        $this->assertEmpty($form->xpath('.//input[@name="Channels[' . $this->idFromFixture(Channel::class, 'monthly') . ']"]'));

        $this->assertPartialMatchBySelector('p', 'Submit to unsubscribe from these channels: News');
    }
}
