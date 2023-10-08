<?php

declare(strict_types=1);

namespace App\Tests\Traits\Telegram\Bot;

use App\Service\Telegram\Bot\TelegramBotUpdateHandler;

trait TelegramBotUpdateHandlerProviderTrait
{
    public function getTelegramBotUpdateHandler(): TelegramBotUpdateHandler
    {
        return static::getContainer()->get('app.telegram_bot_update_handler');
    }
}