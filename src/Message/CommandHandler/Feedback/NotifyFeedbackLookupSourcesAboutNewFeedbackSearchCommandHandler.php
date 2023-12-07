<?php

declare(strict_types=1);

namespace App\Message\CommandHandler\Feedback;

use App\Entity\Feedback\FeedbackLookup;
use App\Entity\Feedback\FeedbackNotification;
use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Enum\Feedback\FeedbackNotificationType;
use App\Enum\Messenger\Messenger;
use App\Enum\Telegram\TelegramBotGroupName;
use App\Message\Command\Feedback\NotifyFeedbackLookupSourcesAboutNewFeedbackSearchCommand;
use App\Message\Event\ActivityEvent;
use App\Repository\Feedback\FeedbackSearchRepository;
use App\Service\Feedback\FeedbackLookupSearcher;
use App\Service\IdGenerator;
use App\Service\Search\Viewer\Telegram\SearchRegistryTelegramSearchViewer;
use App\Service\Telegram\Bot\Api\TelegramBotMessageSenderInterface;
use App\Service\Telegram\Bot\TelegramBotProvider;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotifyFeedbackLookupSourcesAboutNewFeedbackSearchCommandHandler
{
    public function __construct(
        private readonly FeedbackSearchRepository $feedbackSearchRepository,
        private readonly LoggerInterface $logger,
        private readonly FeedbackLookupSearcher $feedbackLookupSearcher,
        private readonly TelegramBotProvider $telegramBotProvider,
        private readonly TranslatorInterface $translator,
        private readonly SearchRegistryTelegramSearchViewer $searchRegistryTelegramSearchViewer,
        private readonly TelegramBotMessageSenderInterface $telegramBotMessageSender,
        private readonly IdGenerator $idGenerator,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $eventBus,
    )
    {
    }

    public function __invoke(NotifyFeedbackLookupSourcesAboutNewFeedbackSearchCommand $command): void
    {
        $feedbackSearch = $command->getFeedbackSearch() ?? $this->feedbackSearchRepository->find($command->getFeedbackSearchId());

        if ($feedbackSearch === null) {
            $this->logger->warning(sprintf('No feedback search was found in %s for %s id', __CLASS__, $command->getFeedbackSearchId()));
            return;
        }

        $searchTerm = $feedbackSearch->getSearchTerm();
        $feedbackLookups = $this->feedbackLookupSearcher->searchFeedbackLookups($searchTerm);

        foreach ($feedbackLookups as $feedbackLookup) {
            $messengerUser = $feedbackLookup->getMessengerUser();

            if (
                $messengerUser !== null
                && $messengerUser->getMessenger() === Messenger::telegram
                && $messengerUser->getId() !== $feedbackSearch->getMessengerUser()->getId()
            ) {
                $this->notify($messengerUser, $searchTerm, $feedbackSearch, $feedbackLookup);
            }
        }
    }


    private function notify(
        MessengerUser $messengerUser,
        FeedbackSearchTerm $searchTerm,
        FeedbackSearch $feedbackSearch,
        FeedbackLookup $feedbackLookup
    ): void
    {
        $botIds = $messengerUser->getBotIds();

        if ($botIds === null) {
            return;
        }

        $bots = $this->telegramBotProvider->getCachedTelegramBotsByGroupAndIds(TelegramBotGroupName::feedbacks, $botIds);

        foreach ($bots as $bot) {
            $this->telegramBotMessageSender->sendTelegramMessage(
                $bot,
                $messengerUser->getIdentifier(),
                $this->getNotifyMessage($messengerUser, $bot, $feedbackSearch),
                keepKeyboard: true
            );

            $notification = new FeedbackNotification(
                $this->idGenerator->generateId(),
                FeedbackNotificationType::feedback_lookup_source_about_new_feedback_search,
                $messengerUser,
                $searchTerm,
                feedbackSearch: $feedbackSearch,
                feedbackLookup: $feedbackLookup,
                telegramBot: $bot
            );
            $this->entityManager->persist($notification);

            $this->eventBus->dispatch(new ActivityEvent(entity: $notification, action: 'created'));
        }
    }

    private function getNotifyMessage(MessengerUser $messengerUser, TelegramBot $bot, FeedbackSearch $feedbackSearch): string
    {
        $localeCode = $messengerUser->getUser()->getLocaleCode();
        $message = '👋 ' . $this->translator->trans('might_be_interesting', domain: 'feedbacks.tg.notify', locale: $localeCode);
        $message = '<b>' . $message . '</b>';
        $message .= ':';
        $message .= "\n\n";
        $message .= $this->searchRegistryTelegramSearchViewer->getFeedbackSearchTelegramView(
            $bot,
            $feedbackSearch,
            addSecrets: $messengerUser->getUser()?->getSubscriptionExpireAt() < new DateTimeImmutable(),
            addCountry: true,
            addTime: true,
            addQuotes: true,
            locale: $localeCode
        );

        return $message;
    }
}