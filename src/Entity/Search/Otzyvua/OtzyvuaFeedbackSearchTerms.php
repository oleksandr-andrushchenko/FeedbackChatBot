<?php

declare(strict_types=1);

namespace App\Entity\Search\Otzyvua;

readonly class OtzyvuaFeedbackSearchTerms
{
    public function __construct(
        private array $items
    )
    {
    }

    public function getItems(): array
    {
        return $this->items;
    }
}