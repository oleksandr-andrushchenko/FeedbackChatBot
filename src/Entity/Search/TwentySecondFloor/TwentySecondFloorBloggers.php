<?php

declare(strict_types=1);

namespace App\Entity\Search\TwentySecondFloor;

readonly class TwentySecondFloorBloggers
{
    public function __construct(
        private array $items
    )
    {
    }

    /**
     * @return TwentySecondFloorBlogger[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}