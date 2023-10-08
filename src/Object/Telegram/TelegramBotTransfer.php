<?php

declare(strict_types=1);

namespace App\Object\Telegram;

use App\Entity\Intl\Country;
use App\Entity\Intl\Locale;
use App\Enum\Telegram\TelegramGroup;

class TelegramBotTransfer
{
    public function __construct(
        private readonly string $username,
        private ?TelegramGroup $group = null,
        private bool $groupPassed = false,
        private ?string $name = null,
        private bool $namePassed = false,
        private ?string $token = null,
        private bool $tokenPassed = false,
        private ?Country $country = null,
        private bool $countryPassed = false,
        private ?Locale $locale = null,
        private bool $localePassed = false,
        private ?bool $checkUpdates = null,
        private bool $checkUpdatesPassed = false,
        private ?bool $checkRequests = null,
        private bool $checkRequestsPassed = false,
        private ?bool $acceptPayments = null,
        private bool $acceptPaymentsPassed = false,
        private ?bool $adminOnly = null,
        private bool $adminOnlyPassed = false,
        private ?array $adminIds = null,
        private bool $adminIdsPassed = false,
        private ?bool $syncTexts = null,
        private bool $syncTextsPassed = false,
        private ?bool $syncWebhook = null,
        private bool $syncWebhookPassed = false,
        private ?bool $syncCommands = null,
        private bool $syncCommandsPassed = false,
        private ?bool $primary = null,
        private bool $primaryPassed = false,
    )
    {
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setGroup(TelegramGroup $group): self
    {
        $this->group = $group;
        $this->groupPassed = true;

        return $this;
    }

    public function groupPassed(): bool
    {
        return $this->groupPassed;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        $this->namePassed = true;

        return $this;
    }

    public function namePassed(): bool
    {
        return $this->namePassed;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;
        $this->tokenPassed = true;

        return $this;
    }

    public function tokenPassed(): bool
    {
        return $this->tokenPassed;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(Country $country): self
    {
        $this->country = $country;
        $this->countryPassed = true;

        return $this;
    }

    public function countryPassed(): bool
    {
        return $this->countryPassed;
    }

    public function getLocale(): ?Locale
    {
        return $this->locale;
    }

    public function setLocale(?Locale $locale): self
    {
        $this->locale = $locale;
        $this->localePassed = true;

        return $this;
    }

    public function localePassed(): bool
    {
        return $this->localePassed;
    }

    public function getGroup(): ?TelegramGroup
    {
        return $this->group;
    }

    public function checkUpdates(): ?bool
    {
        return $this->checkUpdates;
    }

    public function setCheckUpdates(bool $checkUpdates): self
    {
        $this->checkUpdates = $checkUpdates;
        $this->checkUpdatesPassed = true;

        return $this;
    }

    public function checkUpdatesPassed(): bool
    {
        return $this->checkUpdatesPassed;
    }

    public function checkRequests(): ?bool
    {
        return $this->checkRequests;
    }

    public function setCheckRequests(bool $checkRequests): self
    {
        $this->checkRequests = $checkRequests;
        $this->checkRequestsPassed = true;

        return $this;
    }

    public function checkRequestsPassed(): bool
    {
        return $this->checkRequestsPassed;
    }

    public function acceptPayments(): ?bool
    {
        return $this->acceptPayments;
    }

    public function setAcceptPayments(bool $acceptPayments): self
    {
        $this->acceptPayments = $acceptPayments;
        $this->acceptPaymentsPassed = true;

        return $this;
    }

    public function acceptPaymentsPassed(): bool
    {
        return $this->acceptPaymentsPassed;
    }

    public function adminOnly(): ?bool
    {
        return $this->adminOnly;
    }

    public function setAdminOnly(bool $adminOnly): self
    {
        $this->adminOnly = $adminOnly;
        $this->adminOnlyPassed = true;

        return $this;
    }

    public function adminOnlyPassed(): bool
    {
        return $this->adminOnlyPassed;
    }

    public function getAdminIds(): ?array
    {
        return $this->adminIds;
    }

    public function setAdminIds(array $adminIds): self
    {
        $this->adminIds = $adminIds;
        $this->adminIdsPassed = true;

        return $this;
    }

    public function adminIdsPassed(): bool
    {
        return $this->adminIdsPassed;
    }

    public function syncTexts(): ?bool
    {
        return $this->syncTexts;
    }

    public function setSyncTexts(bool $syncTexts): self
    {
        $this->syncTexts = $syncTexts;
        $this->syncTextsPassed = true;

        return $this;
    }

    public function syncTextsPassed(): bool
    {
        return $this->syncTextsPassed;
    }

    public function syncWebhook(): ?bool
    {
        return $this->syncWebhook;
    }

    public function setSyncWebhook(bool $syncWebhook): self
    {
        $this->syncWebhook = $syncWebhook;
        $this->syncWebhookPassed = true;

        return $this;
    }

    public function syncWebhookPassed(): bool
    {
        return $this->syncWebhookPassed;
    }

    public function syncCommands(): ?bool
    {
        return $this->syncCommands;
    }

    public function setSyncCommands(bool $syncCommands): self
    {
        $this->syncCommands = $syncCommands;
        $this->syncCommandsPassed = true;

        return $this;
    }

    public function syncCommandsPassed(): bool
    {
        return $this->syncCommandsPassed;
    }

    public function primary(): ?bool
    {
        return $this->primary;
    }

    public function setPrimary(bool $primary): self
    {
        $this->primary = $primary;
        $this->primaryPassed = true;

        return $this;
    }

    public function primaryPassed(): bool
    {
        return $this->primaryPassed;
    }
}