<?php

declare(strict_types=1);

namespace App\Transfer\Feedback;

use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;
use App\Transfer\Messenger\MessengerUserTransfer;

class SearchTermTransfer
{
    public function __construct(
        private readonly string $text,
        private ?SearchTermType $type = null,
        private ?string $normalizedText = null,
        private ?Messenger $messenger = null,
        private ?string $messengerProfileUrl = null,
        private ?string $messengerUsername = null,
        private ?MessengerUserTransfer $messengerUser = null,
        /**
         * @var null|SearchTermType[]
         */
        private ?array $types = null,
    )
    {
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getNormalizedText(): ?string
    {
        return $this->normalizedText;
    }

    public function setNormalizedText(?string $normalizedText): self
    {
        $this->normalizedText = $normalizedText;

        return $this;
    }

    public function getType(): ?SearchTermType
    {
        return $this->type;
    }

    public function setType(?SearchTermType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getMessengerUsername(): ?string
    {
        return $this->messengerUsername;
    }

    public function setMessengerUsername(?string $messengerUsername): self
    {
        $this->messengerUsername = $messengerUsername;

        return $this;
    }

    public function getMessenger(): ?Messenger
    {
        return $this->messenger;
    }

    public function setMessenger(?Messenger $messenger): self
    {
        $this->messenger = $messenger;

        return $this;
    }

    public function getMessengerUser(): ?MessengerUserTransfer
    {
        return $this->messengerUser;
    }

    public function setMessengerUser(?MessengerUserTransfer $messengerUser): self
    {
        $this->messengerUser = $messengerUser;

        return $this;
    }

    public function getMessengerProfileUrl(): ?string
    {
        return $this->messengerProfileUrl;
    }

    public function setMessengerProfileUrl(?string $messengerProfileUrl): self
    {
        $this->messengerProfileUrl = $messengerProfileUrl;

        return $this;
    }

    public function getTypes(): ?array
    {
        return $this->types;
    }

    public function setTypes(?array $types): self
    {
        $this->types = $types;

        return $this;
    }

    public function addType(SearchTermType $type): self
    {
        if ($this->types === null) {
            $this->types = [];
        }

        $this->types[] = $type;

        return $this;
    }

    public function __toString()
    {
        return $this->text;
    }
}
