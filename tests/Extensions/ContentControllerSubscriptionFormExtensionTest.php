<?php

namespace Mhe\Newsletter\Tests\Extensions;

use Mhe\Newsletter\Test\ThemedTest;

class ContentControllerSubscriptionFormExtensionTest extends ThemedTest
{
    protected static $fixture_file = 'ContentControllerSubscriptionFormExtensionTest.yml';

    protected $autoFollowRedirection = true;

    /*
     * forms are output on page via template, referencing the specific controller
     */
    public function testSubscriptionFormsArePresent(): void
    {
        $this->get('home');
        $form = $this->cssParser()->getBySelector('form');
        $this->assertEquals(2, count($form));
        $this->assertEquals("subscription/SubscriptionForm", $form[0]['action']);
        $this->assertEquals("subscription/SubscriptionForm", $form[1]['action']);
    }
}
