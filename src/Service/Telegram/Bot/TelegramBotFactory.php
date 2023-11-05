<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot;

use App\Entity\Telegram\TelegramBot as TelegramBotEntity;
use Psr\Log\LoggerInterface;

class TelegramBotFactory
{
    public function __construct(
        private readonly TelegramBotClientRegistry $telegramBotClientRegistry,
        private readonly TelegramBotRequestChecker $telegramBotRequestChecker,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function createTelegramBot(TelegramBotEntity $entity): TelegramBot
    {
        return new TelegramBot(
            $entity,
            $this->telegramBotClientRegistry,
            $this->telegramBotRequestChecker,
            $this->logger,
        );
    }
}
