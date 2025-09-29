<?php

namespace Mhe\Newsletter\Tests\Forms;

use Mhe\Newsletter\Model\Channel;
use Page;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\View\SSViewer;

class SubscriptionFormTest extends FunctionalTest
{
    protected static $fixture_file = 'SubscriptionFormTest.yml';

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

    /*
     * The standard form has all expected fields, including checkboxes for channel selection
     */
    public function testSubscriptionFormStandardHasAllFields(): void
    {
        $this->get('home');
        $form = $this->cssParser()->getBySelector('form#SubscriptionForm_SubscriptionForm')[0];
        $this->assertNotEmpty($form->xpath('.//input[@name="FullName"]'));
        $this->assertNotEmpty($form->xpath('.//input[@name="Email"]'));
        $channelSelect = $form->xpath('.//input[@name="Channels[' . $this->idFromFixture(Channel::class, 'news'). ']"]')[0] ?? null;
        $this->assertIsObject($channelSelect);
        $this->assertEquals($channelSelect['type'], "checkbox");
        $this->assertEquals((int)$channelSelect['value'], $this->idFromFixture(Channel::class, 'news'));
        $channelSelect = $form->xpath('.//input[@name="Channels[' . $this->idFromFixture(Channel::class, 'monthly'). ']"]')[0] ?? null;
        $this->assertIsObject($channelSelect);
        $this->assertEquals($channelSelect['type'], "checkbox");
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
        $channelSelect = $form->xpath('.//input[@name="Channels[' . $this->idFromFixture(Channel::class, 'monthly'). ']"]')[0] ?? null;
        $this->assertIsObject($channelSelect);
        $this->assertEquals($channelSelect['type'], "hidden");
        $this->assertEquals((int)$channelSelect['value'], $this->idFromFixture(Channel::class, 'monthly'));
    }
}
