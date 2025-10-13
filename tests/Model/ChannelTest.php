<?php

namespace Mhe\Newsletter\Tests\Model;

use Mhe\Newsletter\Model\Channel;
use Mhe\Newsletter\Model\Recipient;
use SilverStripe\Dev\SapphireTest;

class ChannelTest extends SapphireTest
{
    protected static $fixture_file = 'ChannelTest.yml';

    public function testActiveSubscriptions()
    {
        $channel = $this->objFromFixture(Channel::class, 'news');
        $subscriptions = $channel->getActiveSubscribers();
        $this->assertCount(2, $subscriptions);
        $this->assertEquals(
            [ $this->idFromFixture(Recipient::class, 'dude'),
              $this->idFromFixture(Recipient::class, 'donny')],
            $subscriptions->Column()
        );

        $channel = $this->objFromFixture(Channel::class, 'weekly');
        $subscriptions = $channel->getActiveSubscribers();
        $this->assertCount(1, $subscriptions);
        $this->assertEquals(
            [ $this->idFromFixture(Recipient::class, 'donny') ],
            $subscriptions->Column()
        );
    }
}
