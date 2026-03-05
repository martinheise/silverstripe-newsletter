<?php

namespace Mhe\Newsletter\Tests\Forms;

use Mhe\Newsletter\Forms\SubscriptionForm;
use Mhe\Newsletter\Model\Channel;
use Mhe\Newsletter\Test\ThemedTest;
use PHPUnit\Framework\Attributes\DataProvider;

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


    /**
     * data provider for form validation tests
     * @return array[]
     */
    public static function provideFormValidator(): array
    {
        return [
            'valid input' => [
                'data' => [
                    'Email' => 'info@example.com',
                    'Channels' => [1],
                    'Terms' => 1
                ],
                'expected' => true,
            ],
            'not a valid email address' => [
                'data' => [
                    'Email' => 'invalid',
                    'Channels' => [1],
                    'Terms' => 1
                ],
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('provideFormValidator')]
    public function testSubscriptionFormValidation(array $data, bool $expected): void
    {
        $form = SubscriptionForm::create_default();
        $form->loadDataFrom($data);
        $result = $form->validate();
        $this->assertSame($expected, $result->isValid());
    }
}
