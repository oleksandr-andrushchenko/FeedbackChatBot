<?php

declare(strict_types=1);

namespace App\Entity\Feedback\Command;

readonly class FeedbackCommandOptions
{
    public function __construct(
        private array $limits,
    )
    {
    }

    /**
     * @return FeedbackCommandLimit[]
     */
    public function getLimits(): array
    {
        return $this->limits;
    }
}
