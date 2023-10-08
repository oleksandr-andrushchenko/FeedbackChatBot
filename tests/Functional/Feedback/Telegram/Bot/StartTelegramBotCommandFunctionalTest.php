<?php

declare(strict_types=1);

namespace App\Tests\Functional\Feedback\Telegram\Bot;

use App\Entity\Telegram\TelegramBot;
use App\Service\Feedback\Telegram\Bot\FeedbackTelegramBotGroup;
use App\Tests\Functional\Telegram\Bot\TelegramBotCommandFunctionalTestCase;
use App\Tests\Traits\Intl\CountryProviderTrait;

class StartTelegramBotCommandFunctionalTest extends TelegramBotCommandFunctionalTestCase
{
    use CountryProviderTrait;

    public function testStartSuccess(): void
    {
        $this->bootFixtures([
            TelegramBot::class,
        ]);

        $this
            ->type(FeedbackTelegramBotGroup::START)
        ;

        $this->assertNotNull($this->getUpdateMessengerUser());
        $user = $this->getUpdateMessengerUser()->getUser();
        $this->assertNotNull($user);

        $bot = $this->getBot()->getEntity();
        $botCountry = $this->getCountryProvider()->getCountry($bot->getCountryCode());

        $this->assertEquals($botCountry->getCode(), $user->getCountryCode());
        $this->assertEquals($botCountry->getCurrencyCode(), $user->getCurrencyCode());
        $this->assertEquals($bot->getLocaleCode(), $user->getLocaleCode());
        $this->assertEquals($botCountry->getTimezones()[0] ?? null, $user->getTimezone());

        $this
            ->shouldNotSeeActiveConversation()
            ->shouldSeeReply(
                'title',
                'agreements',
            )
            ->shouldSeeChooseAction()
        ;
    }
}