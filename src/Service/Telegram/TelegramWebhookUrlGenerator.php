<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TelegramWebhookUrlGenerator
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $route,
        private readonly UrlGeneratorInterface $urlGenerator,
    )
    {
    }

    public function generate(string $username): string
    {
        return join('', [
            $this->baseUrl,
            $this->urlGenerator->generate(
                $this->route,
                [
                    'username' => $username,
                ],
                UrlGeneratorInterface::ABSOLUTE_PATH
            ),
        ]);
    }
}