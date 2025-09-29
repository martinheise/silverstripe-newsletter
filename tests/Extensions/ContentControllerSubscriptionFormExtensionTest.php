<?php

namespace Mhe\Newsletter\Tests\Extensions;

use Page;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\View\SSViewer;

class ContentControllerSubscriptionFormExtensionTest extends FunctionalTest
{
    protected static $fixture_file = 'ContentControllerSubscriptionFormExtensionTest.yml';

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
     * forms are output on page via template, referencing the specific controller
     */
    public function testSubscriptionFormsArePresent(): void
    {
        $this->get('home');
        $form = $this->cssParser()->getBySelector('form');
        $this->assertEquals(2, count($form));
        $this->assertEquals($form[0]['action'], "subscription/SubscriptionForm");
        $this->assertEquals($form[1]['action'], "subscription/SubscriptionForm");
    }

}
