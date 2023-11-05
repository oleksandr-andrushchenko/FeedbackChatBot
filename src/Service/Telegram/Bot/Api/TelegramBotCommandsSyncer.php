<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Api;

use App\Entity\Telegram\TelegramBot as TelegramBotEntity;
use App\Entity\Telegram\TelegramBotCommand;
use App\Entity\Telegram\TelegramBotMyCommands;
use App\Service\Telegram\Bot\TelegramBot;
use App\Service\Telegram\Bot\TelegramBotMyCommandsProvider;
use App\Service\Telegram\Bot\TelegramBotRegistry;
use Symfony\Contracts\Translation\TranslatorInterface;

class TelegramBotCommandsSyncer
{
    public function __construct(
        private readonly TelegramBotRegistry $telegramBotRegistry,
        private readonly TranslatorInterface $translator,
        private readonly TelegramBotMyCommandsProvider $telegramBotMyCommandsProvider,
        private ?array $myCommands = null,
    )
    {
        $this->myCommands = null;
    }

    public function syncTelegramCommands(TelegramBotEntity $botEntity): void
    {
        $bot = $this->telegramBotRegistry->getTelegramBot($botEntity);
        $this->myCommands = [];

        foreach ($this->telegramBotMyCommandsProvider->getTelegramMyCommands($bot) as $myCommands) {
            $data = [
                'scope' => $myCommands->getScope()->jsonSerialize(),
                'commands' => array_map(
                    fn (TelegramBotCommand $command): array => [
                        'command' => $command->getName(),
                        'description' => $this->getDescription($bot, $command, $myCommands),
                    ],
                    $myCommands->getCommands()
                ),
            ];

            $this->myCommands[] = $myCommands;

            $bot->setMyCommands($data);
        }

        $bot->getEntity()->setCommandsSynced(true);
    }

    /**
     * @return array|null|TelegramBotMyCommands[]
     */
    public function getMyCommands(): ?array
    {
        return $this->myCommands;
    }

    private function getDescription(
        TelegramBot $bot,
        TelegramBotCommand $command,
        TelegramBotMyCommands $myCommands
    ): string
    {
        $domain = sprintf('%s.tg.command', $bot->getEntity()->getGroup()->name);
        $locale = $myCommands->getLocaleCode();

        $icon = $this->translator->trans($command->getKey(), domain: $domain, locale: $locale);
        $name = $this->translator->trans($command->getKey(), domain: $domain, locale: $locale);

        return sprintf('%s %s', $icon, $name);
    }
}