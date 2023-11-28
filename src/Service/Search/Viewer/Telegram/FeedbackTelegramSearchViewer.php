<?php

declare(strict_types=1);

namespace App\Service\Search\Viewer\Telegram;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\FeedbackSearchTerm;
use App\Service\Feedback\Telegram\Bot\View\FeedbackTelegramViewProvider;
use App\Service\Search\Viewer\SearchViewer;
use App\Service\Search\Viewer\SearchViewerHelper;
use App\Service\Search\Viewer\SearchViewerInterface;

class FeedbackTelegramSearchViewer extends SearchViewer implements SearchViewerInterface
{
    public function __construct(
        SearchViewerHelper $searchViewerHelper,
        private readonly FeedbackTelegramViewProvider $feedbackTelegramViewProvider,
    )
    {
        parent::__construct($searchViewerHelper->withTransDomain('feedback'));
    }

    public function getResultRecord($record, FeedbackSearchTerm $searchTerm, array $context = []): string
    {
        $full = $context['full'] ?? false;

        $message = '💫 ';
        $message .= $this->searchViewerHelper->wrapResultRecord(
            $this->searchViewerHelper->trans('feedbacks_title'),
            $record,
            fn (Feedback $feedback): array => [
                $this->feedbackTelegramViewProvider->getFeedbackTelegramView(
                    $context['bot'] ?? $feedback->getTelegramBot(),
                    $feedback,
                    addSecrets: !$full,
                    addTime: true,
                    addCountry: true,
                ),
            ],
            $full
        );

        return $message;
    }
}
