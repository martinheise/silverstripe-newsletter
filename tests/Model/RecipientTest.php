<?php

namespace Mhe\Newsletter\Tests\Model;

use Mhe\Newsletter\Model\Recipient;
use SilverStripe\Dev\SapphireTest;

class RecipientTest extends SapphireTest
{
    protected static $fixture_file = 'RecipientTest.yml';

    public function testActiveSubscriptions()
    {
        $recipient = $this->objFromFixture(Recipient::class, 'dude');
        $subscriptions = $recipient->getActiveSubscriptions();
        $this->assertCount(1, $subscriptions);
        $this->assertEquals('News', $subscriptions[0]->Title);
    }
}
