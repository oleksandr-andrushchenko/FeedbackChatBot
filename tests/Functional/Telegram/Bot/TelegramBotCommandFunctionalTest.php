<?php

declare(strict_types=1);

namespace App\Tests\Functional\Telegram\Bot;

use App\Entity\Telegram\TelegramBot;
use App\Tests\Fixtures;
use DateTimeImmutable;

class TelegramBotCommandFunctionalTest extends TelegramBotCommandFunctionalTestCase
{
    public function testDeletedWithoutReplacementTelegramBotRequest(): void
    {
        $this->bootFixtures([
            TelegramBot::class,
        ]);

        $bot = $this->getTelegramBotRepository()->findOneByUsername(Fixtures::BOT_USERNAME_1);
        $bot->setDeletedAt(new DateTimeImmutable());
        $this->getEntityManager()->flush();

        $this->typeText('any');

        $this->assertEmpty($this->getTelegramBotMessageSender()->getCalls());
    }

    public function testDeletedWithReplacementTelegramBotRequest(): void
    {
        $this->bootFixtures([
            TelegramBot::class,
        ]);

        $bot = $this->getTelegramBotRepository()->findOneByUsername(Fixtures::BOT_USERNAME_1);
        $bot->setDeletedAt(new DateTimeImmutable());
        $entityManager = $this->getEntityManager();
        $newBot = $this->copyBot($bot);
        $entityManager->persist($newBot);
        $entityManager->flush();

        $this->typeText('any');

        $this->shouldSeeReply(
            'reply.use_primary',
            $newBot->getUsername(),
            $newBot->getName(),
        );
    }

    public function testNonPrimaryWithoutReplacementTelegramBotRequest(): void
    {
        $this->bootFixtures([
            TelegramBot::class,
        ]);

        $bot = $this->getTelegramBotRepository()->findOneByUsername(Fixtures::BOT_USERNAME_1);
        $bot->setPrimary(false);
        $this->getEntityManager()->flush();

        $this->typeText('any');

        $this->assertEmpty($this->getTelegramBotMessageSender()->getCalls());
    }

    public function testNonPrimaryWithReplacementTelegramBotRequest(): void
    {
        $this->bootFixtures([
            TelegramBot::class,
        ]);

        $bot = $this->getTelegramBotRepository()->findOneByUsername(Fixtures::BOT_USERNAME_1);
        $bot->setPrimary(false);
        $entityManager = $this->getEntityManager();
        $newBot = $this->copyBot($bot);
        $entityManager->persist($newBot);
        $entityManager->flush();

        $this->typeText('any');

        $this->shouldSeeReply(
            'reply.use_primary',
            $newBot->getUsername(),
            $newBot->getName(),
        );
    }

    private function copyBot(TelegramBot $bot): TelegramBot
    {
        return new TelegramBot(
            $bot->getUsername() . '_copy',
            $bot->getGroup(),
            $bot->getName() . ' Copy',
            'token',
            $bot->getCountryCode(),
            $bot->getLocaleCode()
        );
    }
}