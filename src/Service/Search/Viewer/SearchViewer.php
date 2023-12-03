<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer;

use App\Entity\Feedback\FeedbackSearchTerm;
use App\Service\Modifier;

abstract class SearchViewer implements SearchViewerInterface
{
    public function __construct(
        protected readonly SearchViewerCompose $searchViewerCompose,
        protected readonly Modifier $modifier,
    )
    {
    }

    public function getOnSearchMessage(FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        return '🔍 ' . $this->trans('on_search');
    }

    public function getEmptyMessage(FeedbackSearchTerm $searchTerm, array $context = [], bool $good = null): string
    {
        $message = '';

        if ($good) {
            // ✅☑️☀️👍🟢✔️
            $message .= '☑️ ';
        }

        $message .= $this->trans('empty_result', generalDomain: true);

        if ($good) {
            $message .= ' ';
            $message .= $this->trans('all_good', generalDomain: true);
        }

        return $message;
    }

    public function getErrorMessage(FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        return $this->trans('error_result', generalDomain: true);
    }

    protected function implodeResult(string $title, array $items, callable $record, bool $full): string
    {
        // 🔴🟡🟢⚪️🚨‼️
        // ⬜️⬛️◻️◼️◽️◾️▫️▪️
        // 💥🔥✨⚡️💫🥳🤩

        $messages = [];

        $messages[] = $this->modifier->create()
            ->add($this->modifier->boldModifier())
            ->add($this->modifier->underlineModifier())
            ->apply($title)
        ;

        $count = count($items);

        if ($full) {
            $maxResults = $count;
        } else {
            $maxResults = intval($count * .1);
            $maxResults = max($maxResults, 1);
        }

        $added = 0;

        foreach ($items as $item) {
            $messages[] = '◻️ ' . implode("\n▫️ ", $this->normalizeAndFilterEmptyStrings($record($item)));
            $added++;

            if ($added === $maxResults) {
                break;
            }
        }

        if (!$full) {
            $message = '🔒 ';

            if ($maxResults !== $count) {
                $message .= $this->modifier->create()
                    ->add($this->modifier->italicModifier())
                    ->apply($this->transSubscriptionSkippedRecords($maxResults, $count))
                ;
            }

            $message .= ' ';
            $message .= $this->modifier->create()
                ->add($this->modifier->italicModifier())
                ->apply($this->transSubscriptionSkippedData())
            ;
            $message .= ' ';
            $message .= $this->modifier->create()
                ->add($this->modifier->italicModifier())
                ->apply($this->transSubscriptionSkippedLinks())
            ;
            $message .= ' ';
            $message .= $this->modifier->create()
                ->add($this->modifier->italicModifier())
                ->apply($this->transSubscriptionBenefits())
            ;

            $messages[] = $message;
        }

        return implode("\n\n", $messages);
    }

    protected function transSubscriptionSkippedRecords(int $maxResults, int $count): string
    {
        $parameters = [
            'shown_count' => $this->modifier->create()
                ->add($this->modifier->boldModifier())
                ->apply($maxResults),
            'total_count' => $this->modifier->create()
                ->add($this->modifier->boldModifier())
                ->apply($count),
        ];

        return $this->trans('subscription_skipped_records', $parameters, generalDomain: true);
    }

    protected function transSubscriptionSkippedData(): string
    {
        return $this->trans('subscription_skipped_data', generalDomain: true);
    }

    protected function transSubscriptionSkippedLinks(): string
    {
        return $this->trans('subscription_skipped_links', generalDomain: true);
    }

    protected function transSubscriptionBenefits(): string
    {
        $parameters = [
            'all_records' => $this->modifier->create()
                ->add($this->modifier->boldModifier())
                ->apply($this->trans('subscription_all_records', generalDomain: true)),
            'all_links' => $this->modifier->create()
                ->add($this->modifier->boldModifier())
                ->apply($this->trans('subscription_all_links', generalDomain: true)),
            'all_data' => $this->modifier->create()
                ->add($this->modifier->boldModifier())
                ->apply($this->trans('subscription_all_data', generalDomain: true)),
            'subscribe_command' => $this->modifier->create()
                ->add($this->modifier->boldModifier())
                ->apply('/subscribe'),
        ];

        return $this->trans('subscription_benefits', $parameters, generalDomain: true);
    }

    protected function normalizeAndFilterEmptyStrings(array $input): array
    {
        $output = [];

        foreach ($input as $item) {
            if (empty($item)) {
                continue;
            }

            $item = trim($item);
            $item = preg_replace('/\s+/', ' ', $item);

            $noTagsItem = strip_tags($item);
            $noTagsItem = trim($noTagsItem);

            if (!empty($item) && !empty($noTagsItem)) {
                $output[] = $item;
            }
        }

        return $output;
    }

    protected function trans($id, array $parameters = [], bool $generalDomain = false): string
    {
        return $this->searchViewerCompose->trans($id, $parameters, $generalDomain);
    }
}
