<?php

declare(strict_types=1);

namespace App\Entity\Search\Clarity;

readonly class ClarityPersonEdrsRecord
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