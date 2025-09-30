<?php

namespace Mhe\Newsletter\Tests\Model;

use Mhe\Newsletter\Model\Recipient;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBDatetime;

class RecipientTest extends SapphireTest
{
    protected static $fixture_file = 'RecipientTest.yml';

    public function setUp(): void
    {
        parent::setUp();
        DBDatetime::set_mock_now('2025-01-15T08:15:00');
    }

    public function tearDown(): void
    {
        DBDatetime::clear_mock_now();
        parent::tearDown();
    }

    public function testActiveSubscriptions()
    {
        $recipient = $this->objFromFixture(Recipient::class, 'dude');
        $subscriptions = $recipient->getActiveSubscriptions();
        $this->assertCount(1, $subscriptions);
        $this->assertEquals('News', $subscriptions[0]->Title);
    }


    /*
     * ToDo: more tests
     */

}
