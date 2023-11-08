<?php

declare(strict_types=1);

namespace App\Message\Command\Feedback;

use App\Message\Event\Feedback\FeedbackEvent;

readonly class NotifyFeedbackSourcesAboutNewFeedbackCommand extends FeedbackEvent
{
}