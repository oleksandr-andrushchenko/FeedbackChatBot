<?php

declare(strict_types=1);

namespace App\Service\Search\Provider;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Search\Blackbox\BlackboxFeedback;
use App\Entity\Search\Blackbox\BlackboxFeedbacks;
use App\Enum\Feedback\SearchTermType;
use App\Enum\Search\SearchProviderName;
use App\Service\CrawlerProvider;
use App\Service\HttpRequester;
use DateTimeImmutable;

/**
 * @see https://blackbox.net.ua/
 * @see https://blackbox.net.ua/0667524039/
 */
class BlackboxSearchProvider extends SearchProvider implements SearchProviderInterface
{
    private const URL = 'https://blackbox.net.ua';

    public function __construct(
        SearchProviderHelper $searchProviderHelper,
        private readonly CrawlerProvider $crawlerProvider,
        private readonly HttpRequester $httpRequester,
    )
    {
        parent::__construct($searchProviderHelper);
    }

    public function getName(): SearchProviderName
    {
        return SearchProviderName::blackbox;
    }

    public function supports(FeedbackSearchTerm $searchTerm, array $context = []): bool
    {
        $countryCode = $context['countryCode'] ?? null;

        if ($countryCode !== 'ua') {
            return false;
        }

        $type = $searchTerm->getType();
        $term = $searchTerm->getNormalizedText();

        if ($type === SearchTermType::phone_number) {
            if (!str_starts_with($term, '380')) {
                return false;
            }

            return true;
        }

        if ($type === SearchTermType::person_name) {
            return true;
        }

        return false;
    }

    public function search(FeedbackSearchTerm $searchTerm, array $context = []): array
    {
        $type = $searchTerm->getType();
        $term = $searchTerm->getNormalizedText();

        $feedbacks = $this->searchProviderHelper->tryCatch(fn () => $this->searchFeedbacks($type, $term), null);

        if ($feedbacks === null) {
            return [];
        }

        if (count($feedbacks->getItems()) === 1) {
            return [
                $feedbacks->getItems()[0],
            ];
        }

        return [
            $feedbacks,
        ];
    }

    private function getToken(): ?string
    {
        static $token = null;

        if ($token !== null) {
            return $token;
        }

        $crawler = $this->crawlerProvider->getCrawler('GET', self::URL, user: true);
        $tokenEl = $crawler->filter('#check-man-form [name="csrfmiddlewaretoken"]');

        if ($tokenEl->count() === 0) {
            return null;
        }

        $token = trim($tokenEl->attr('value') ?? '');

        if (empty($token)) {
            return null;
        }

        return $token;
    }

    private function searchFeedbacks(SearchTermType $type, string $term): ?BlackboxFeedbacks
    {
        $token = $this->getToken();

        if ($token === null) {
            return null;
        }

        sleep(1);

        $url = self::URL . '/check/';
        $headers = [
            'Referer' => self::URL,
        ];
        $body = [
            'csrfmiddlewaretoken' => $token,
        ];

        if ($type === SearchTermType::phone_number) {
            $body['type'] = 1;
            $d = str_split($term);
            $body['phone_number'] = sprintf('+38(0%d%d)%d%d%d-%d%d-%d%d', $d[3], $d[4], $d[5], $d[6], $d[7], $d[8], $d[9], $d[10], $d[11]);
        } elseif ($type === SearchTermType::person_name) {
            $body['type'] = 2;
            $body['last_name'] = explode(' ', $term)[0];
        }

        $content = $this->searchProviderHelper->tryCatch(
            fn () => $this->httpRequester->requestHttp('POST', $url, headers: $headers, body: $body, user: true, array: true),
            [],
            [404]
        );
        $rows = $content['data'] ?? [];

        $items = [];

        foreach ($rows as $row) {
            if (!isset($row['fios'], $row['phone'])) {
                continue;
            }

            foreach ($row['fios'] as $index => $name) {
                $track = $row['tracks'][$index] ?? null;

                $items[] = new BlackboxFeedback(
                    name: $name,
                    href: self::URL . '/' . $row['phone'],
                    phone: $row['phone'],
                    phoneFormatted: $row['phone_formatted'] ?? null,
                    comment: empty($track) || empty($track['comment']) ? null : $track['comment'],
                    date: empty($track) || empty($track['date'])
                        ? null
                        : (DateTimeImmutable::createFromFormat(strlen($track['date']) === 10 ? 'Y-m-d' : 'd.m.Y H:i:s', $track['date']) ?: null)?->setTime(0, 0),
                    city: empty($track) || empty($track['city']) ? null : $track['city'],
                    warehouse: empty($track) || empty($track['warehouse']) ? null : $track['warehouse'],
                    cost: empty($track) || empty($track['cost']) ? null : $track['cost'],
                    type: empty($track) || empty($track['type']) ? null : $track['type']
                );
            }
        }

        $items = array_filter($items, static function (BlackboxFeedback $feedback): bool {
            // todo: filter
            return true;
        });

        return count($items) === 0 ? null : new BlackboxFeedbacks(array_values($items));
    }
}
