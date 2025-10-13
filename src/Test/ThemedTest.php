<?php

namespace Mhe\Newsletter\Test;

use Exception;
use Page;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\View\SSViewer;

/**
 * helper class for common testing
 */
class ThemedTest extends FunctionalTest
{
    protected static string $test_theme = 'test-newsletter';

    protected static string $default_now = '2025-01-15T08:15:00';

    /**
     * setup: prepare environment with test theme
     *
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        DBDatetime::set_mock_now(self::$default_now);

        // setup test theme
        $themeBaseDir = realpath(__DIR__ . '/../../tests');
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
}
