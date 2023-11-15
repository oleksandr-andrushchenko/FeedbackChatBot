<?php

declare(strict_types=1);

namespace App\Service\Lookup\Viewer;

use App\Entity\Feedback\FeedbackSearchTerm;

interface LookupViewerInterface
{
    public function getOnSearchTitle(FeedbackSearchTerm $searchTerm, array $context = []): string;

    public function getEmptyResultTitle(FeedbackSearchTerm $searchTerm, array $context = []): string;

    public function getResultRecord($record, array $context = []): string;
}
