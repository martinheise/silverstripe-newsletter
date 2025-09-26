<?php

namespace Mhe\Newsletter\Tests\Forms;

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
     * form is output on page via template
     */
    public function testSubscriptionFormIsPresent(): void
    {
        $this->get('home');
        $form = $this->cssParser()->getBySelector('form#SubscriptionForm_SubscriptionForm');
        $this->assertNotEmpty($form);
    }

    /*
     * form has all expected fields, included a dropdown for channel selection
     */
    public function testSubscriptionFormHasAllFields(): void
    {
        $this->get('home');
        $form = $this->cssParser()->getBySelector('form#SubscriptionForm_SubscriptionForm')[0];
        $this->assertNotEmpty($form->xpath('//input[@name="FullName"]'));
        $this->assertNotEmpty($form->xpath('//input[@name="Email"]'));
        $channelSelect = $form->xpath('//select[@name="Channels"]')[0] ?? null;
        $this->assertIsObject($channelSelect);
    }
}
