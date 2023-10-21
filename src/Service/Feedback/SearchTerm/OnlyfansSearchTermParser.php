<?php

declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Transfer\Feedback\SearchTermTransfer;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Messenger\Messenger;

class OnlyfansSearchTermParser implements SearchTermParserInterface
{
    public function supportsSearchTerm(SearchTermTransfer $searchTerm): bool
    {
        if ($searchTerm->getType() === null) {
            return $this->supportsUsername($searchTerm->getText()) || $this->supportsUrl($searchTerm->getText());
        }

        if ($searchTerm->getType() === SearchTermType::onlyfans_username) {
            return true;
        }

        return false;
    }

    public function parseWithGuessType(SearchTermTransfer $searchTerm): void
    {
        if ($this->supportsUrl($searchTerm->getText(), $username)) {
            $normalizedUsername = $this->normalizeUsername($username);
            $normalizedProfileUrl = $this->makeProfileUrl($normalizedUsername);

            $searchTerm
                ->setNormalizedText($normalizedUsername)
                ->setType(SearchTermType::onlyfans_username)
                ->setMessengerUsername($normalizedUsername)
                ->setMessenger(Messenger::onlyfans)
                ->setMessengerProfileUrl($normalizedProfileUrl)
            ;
        } elseif ($this->supportsUsername($searchTerm->getText())) {
            $searchTerm
                ->addType(SearchTermType::onlyfans_username)
            ;
        }
    }

    public function parseWithKnownType(SearchTermTransfer $searchTerm): void
    {
        if ($searchTerm->getType() === SearchTermType::onlyfans_username) {
            $normalizedUsername = $this->normalizeUsername($searchTerm->getText());

            $searchTerm
                ->setNormalizedText($normalizedUsername === $searchTerm->getText() ? null : $normalizedUsername)
                ->setMessenger(Messenger::onlyfans)
                ->setMessengerUsername($normalizedUsername)
                ->setMessengerProfileUrl($this->makeProfileUrl($normalizedUsername))
            ;
        }
    }

    public function parseWithNetwork(SearchTermTransfer $searchTerm): void
    {
        // TODO: Implement parseWithNetwork() method.
    }

    private function supportsUsername(string $username): bool
    {
        if (is_numeric($username)) {
            return false;
        }

        return preg_match('/^' . $this->getUsernamePattern(false) . '$/im', $username) === 1;
    }

    private function getUsernamePattern(bool $url): string
    {
        return ($url ? '' : '@?') . '\w(?!.*?\.{2})[\w.]{1,28}\w';
    }

    private function normalizeUsername(string $username): string
    {
        return ltrim($username, '@');
    }

    private function makeProfileUrl(string $username): string
    {
        return sprintf('https://onlyfans.com/%s', $username);
    }

    private function supportsUrl(string $url, string &$username = null): bool
    {
        $result = preg_match('/^(?:(?:http|https):\/\/)?(?:www\.)?onlyfans\.com\/(' . $this->getUsernamePattern(true) . ')[?\/]?/im', $url, $matches);

        if ($result === 1 && $this->supportsUsername($matches[1])) {
            $username = $matches[1];

            return true;
        }

        return false;
    }
}
