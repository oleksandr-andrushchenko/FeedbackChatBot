<?php

declare(strict_types=1);

namespace App\Service\Feedback\Notify;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\FeedbackSearchSearchTermUserTelegramNotification;
use App\Entity\Feedback\FeedbackSearchTerm;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Enum\Messenger\Messenger;
use App\Enum\Telegram\TelegramBotGroupName;
use App\Message\Event\Feedback\FeedbackSearchSearchTermUserTelegramNotificationCreatedEvent;
use App\Repository\Messenger\MessengerUserRepository;
use App\Repository\Telegram\Bot\TelegramBotRepository;
use App\Service\Feedback\SearchTerm\SearchTermMessengerProvider;
use App\Service\Feedback\Telegram\Bot\View\FeedbackSearchTelegramViewProvider;
use App\Service\IdGenerator;
use App\Service\Telegram\Bot\Api\TelegramBotMessageSenderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FeedbackSearchSearchTermUsersTelegramNotifier implements FeedbackSearchSearchTermUsersNotifierInterface
{
    public function __construct(
        private readonly SearchTermMessengerProvider $searchTermMessengerProvider,
        private readonly MessengerUserRepository $messengerUserRepository,
        private readonly TelegramBotRepository $telegramBotRepository,
        private readonly TranslatorInterface $translator,
        private readonly FeedbackSearchTelegramViewProvider $feedbackSearchTelegramViewProvider,
        private readonly TelegramBotMessageSenderInterface $telegramBotMessageSender,
        private readonly IdGenerator $idGenerator,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $eventBus,
    )
    {
    }

    public function notifyFeedbackSearchSearchTermUser(FeedbackSearch $feedbackSearch): void
    {
        $searchTerm = $feedbackSearch->getSearchTerm();
        $messengerUser = $searchTerm->getMessengerUser();

        if (
            $messengerUser !== null
            && $messengerUser->getMessenger() === Messenger::telegram
            && $messengerUser->getId() !== $feedbackSearch->getMessengerUser()->getId()
        ) {
            $this->notify($messengerUser, $searchTerm, $feedbackSearch);
            return;
        }

        // todo: process usernames from MessengerUser::$usernameHistory
        // todo: add search across unknown types (check if telegram type in types -> normalize text -> search)

        $messenger = $this->searchTermMessengerProvider->getSearchTermMessenger($searchTerm->getType());

        if ($messenger === Messenger::telegram) {
            $username = $searchTerm->getNormalizedText() ?? $searchTerm->getText();

            $messengerUser = $this->messengerUserRepository->findOneByMessengerAndUsername($messenger, $username);

            if (
                $messengerUser !== null
                && $messengerUser->getId() !== $feedbackSearch->getMessengerUser()->getId()
            ) {
                $this->notify($messengerUser, $searchTerm, $feedbackSearch);
            }
        }
    }

    private function notify(MessengerUser $messengerUser, FeedbackSearchTerm $searchTerm, FeedbackSearch $feedbackSearch): void
    {
        $botIds = $messengerUser->getBotIds();

        if ($botIds === null) {
            return;
        }

        $bots = $this->telegramBotRepository->findPrimaryByGroupAndIds(TelegramBotGroupName::feedbacks, $botIds);

        foreach ($bots as $bot) {
            $this->telegramBotMessageSender->sendTelegramMessage(
                $bot,
                $messengerUser->getIdentifier(),
                $this->getNotifyMessage($messengerUser, $bot, $feedbackSearch),
                keepKeyboard: true
            );

            $notification = new FeedbackSearchSearchTermUserTelegramNotification(
                $this->idGenerator->generateId(),
                $messengerUser,
                $searchTerm,
                $feedbackSearch,
                $bot
            );
            $this->entityManager->persist($notification);

            $this->eventBus->dispatch(new FeedbackSearchSearchTermUserTelegramNotificationCreatedEvent(notification: $notification));
        }
    }

    private function getNotifyMessage(MessengerUser $messengerUser, TelegramBot $bot, FeedbackSearch $feedbackSearch): string
    {
        $localeCode = $messengerUser->getUser()->getLocaleCode();
        $message = '👋 ' . $this->translator->trans('might_be_interesting', domain: 'feedbacks.tg.notify', locale: $localeCode);
        $message = '<b>' . $message . '</b>';
        $message .= ':';
        $message .= "\n\n";
        $message .= $this->feedbackSearchTelegramViewProvider->getFeedbackSearchTelegramView(
            $bot,
            $feedbackSearch,
            addSecrets: true,
            addQuotes: true,
            localeCode: $localeCode
        );

        return $message;
    }
}