<?php

namespace Mhe\Newsletter\Tests\Model;

use Mhe\Newsletter\Model\Channel;
use Mhe\Newsletter\Model\Recipient;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBDatetime;

class ChannelTest extends SapphireTest
{
    protected static $fixture_file = 'ChannelTest.yml';

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
        $channel = $this->objFromFixture(Channel::class, 'news');
        $subscriptions = $channel->getActiveSubscribers();
        $this->assertCount(2, $subscriptions);
        $this->assertEquals([
                $this->idFromFixture(Recipient::class, 'dude'),
                $this->idFromFixture(Recipient::class, 'donny')
            ],
            $subscriptions->Column('ID'));

        $channel = $this->objFromFixture(Channel::class, 'weekly');
        $subscriptions = $channel->getActiveSubscribers();
        $this->assertCount(1, $subscriptions);
        $this->assertEquals([
                $this->idFromFixture(Recipient::class, 'donny')
            ],
            $subscriptions->Column('ID'));
    }
}
