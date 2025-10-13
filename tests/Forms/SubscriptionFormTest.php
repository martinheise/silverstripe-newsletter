<?php

namespace Mhe\Newsletter\Tests\Forms;

use Mhe\Newsletter\Model\Channel;
use Mhe\Newsletter\Test\ThemedTest;

class SubscriptionFormTest extends ThemedTest
{
    protected static $fixture_file = 'SubscriptionFormTest.yml';

    protected $autoFollowRedirection = true;

    /*
     * The standard form has all expected fields, including checkboxes for channel selection
     */
    public function testSubscriptionFormStandardHasAllFields(): void
    {
        $this->get('home');
        $form = $this->cssParser()->getBySelector('form#SubscriptionForm_SubscriptionForm')[0];
        $this->assertNotEmpty($form->xpath('.//input[@name="FullName"]'));
        $this->assertNotEmpty($form->xpath('.//input[@name="Email"]'));
        // key is no frontend field:
        $this->assertEmpty($form->xpath('.//input[@name="Key"]'));
        $channelSelect = $form->xpath('.//input[@name="Channels[' . $this->idFromFixture(Channel::class, 'news') . ']"]')[0] ?? null;
        $this->assertIsObject($channelSelect);
        $this->assertEquals("checkbox", $channelSelect['type']);
        $this->assertEquals((int)$channelSelect['value'], $this->idFromFixture(Channel::class, 'news'));
        $channelSelect = $form->xpath('.//input[@name="Channels[' . $this->idFromFixture(Channel::class, 'monthly') . ']"]')[0] ?? null;
        $this->assertIsObject($channelSelect);
        $this->assertEquals("checkbox", $channelSelect['type']);
        $this->assertEquals((int)$channelSelect['value'], $this->idFromFixture(Channel::class, 'monthly'));
    }

    /*
     * The filtered form has all expected fields, including a hidden field for the desired channel
     */
    public function testSubscriptionFormFilteredHasAllFields(): void
    {
        $this->get('home');
        $form = $this->cssParser()->getBySelector('form#SubscriptionForm_SubscriptionForm_monthly')[0];
        $this->assertNotEmpty($form->xpath('.//input[@name="FullName"]'));
        $this->assertNotEmpty($form->xpath('.//input[@name="Email"]'));
        // key is no frontend field:
        $this->assertEmpty($form->xpath('.//input[@name="Key"]'));
        $channelSelect = $form->xpath('.//input[@name="Channels[' . $this->idFromFixture(Channel::class, 'monthly') . ']"]')[0] ?? null;
        $this->assertIsObject($channelSelect);
        $this->assertEquals("hidden", $channelSelect['type']);
        $this->assertEquals((int)$channelSelect['value'], $this->idFromFixture(Channel::class, 'monthly'));
    }
}
