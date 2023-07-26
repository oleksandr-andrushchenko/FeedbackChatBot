<?php

declare(strict_types=1);

namespace App\Service\Telegram\Channel;

use App\Entity\Telegram\TelegramPayment;
use App\Service\Feedback\FeedbackUserSubscriptionManager;
use App\Service\Intl\CountryProvider;
use App\Service\Telegram\Chat\FeedbackSubscriptionsTelegramChatSender;
use App\Service\Telegram\Conversation\ChooseFeedbackActionTelegramConversation;
use App\Service\Telegram\Conversation\ChooseFeedbackCountryTelegramConversation;
use App\Service\Telegram\Conversation\GetFeedbackPremiumTelegramConversation;
use App\Service\Telegram\Conversation\CreateFeedbackTelegramConversation;
use App\Service\Telegram\Conversation\SearchFeedbackTelegramConversation;
use App\Service\Telegram\FallbackTelegramCommand;
use App\Service\Telegram\TelegramCommand;
use App\Service\Telegram\TelegramAwareHelper;
use App\Service\Telegram\TelegramConversationFactory;

class FeedbackTelegramChannel extends TelegramChannel implements TelegramChannelInterface
{
    public const START = '/start';
    public const CREATE_FEEDBACK = '/add';
    public const SEARCH_FEEDBACK = '/find';
    public const GET_PREMIUM = '/premium';
    public const SUBSCRIPTIONS = '/subscriptions';
    public const COUNTRY = '/country';
    public const RESTART = '/restart';

    public function __construct(
        TelegramAwareHelper $awareHelper,
        TelegramConversationFactory $conversationFactory,
        private readonly CountryProvider $countryProvider,
        private readonly FeedbackUserSubscriptionManager $userSubscriptionManager,
        private readonly FeedbackSubscriptionsTelegramChatSender $subscriptionsChatSender,
    )
    {
        parent::__construct($awareHelper, $conversationFactory);
    }

    /**
     * Should be synced with: ChooseFeedbackActionTelegramConversation
     * @param TelegramAwareHelper $tg
     * @return iterable
     */
    protected function getCommands(TelegramAwareHelper $tg): iterable
    {
        yield new TelegramCommand(self::START, fn () => $this->start($tg), menu: false);
        yield new TelegramCommand(self::CREATE_FEEDBACK, fn () => $this->create($tg), menu: true, key: 'create');
        yield new TelegramCommand(self::SEARCH_FEEDBACK, fn () => $this->search($tg), menu: true, key: 'search');
        yield new TelegramCommand(self::GET_PREMIUM, fn () => $this->premium($tg), menu: false, key: 'premium');
        yield new TelegramCommand(self::SUBSCRIPTIONS, fn () => $this->subscriptions($tg), menu: true, key: 'subscriptions');
        yield new TelegramCommand(self::COUNTRY, fn () => $this->country($tg), menu: true, key: 'country');
        yield new TelegramCommand(self::RESTART, fn () => $this->restart($tg), menu: true, key: 'restart', beforeConversations: true);

        // todo: "who've been looking for me" command
        // todo: "list my feedbacks" command
        // todo: "list feedbacks on me" command
        // todo: "subscribe on mine/somebodies feedbacks" command

        yield new FallbackTelegramCommand(fn () => $this->fallback($tg));
    }

    public function start(TelegramAwareHelper $tg): null
    {
        $countries = $this->countryProvider->getCountries($tg->getLanguageCode());

        if (count($countries) === 1) {
            $country = array_values($countries)[0];

            $tg->getTelegram()->getMessengerUser()?->getUser()->setCountryCode($country->getCode());

            return $tg->startConversation(ChooseFeedbackActionTelegramConversation::class)->null();
        }

        return $tg->startConversation(ChooseFeedbackCountryTelegramConversation::class)->null();
    }

    public function create(TelegramAwareHelper $tg): null
    {
        return $tg->startConversation(CreateFeedbackTelegramConversation::class)->null();
    }

    public function search(TelegramAwareHelper $tg): null
    {
        return $tg->startConversation(SearchFeedbackTelegramConversation::class)->null();
    }

    public function premium(TelegramAwareHelper $tg): null
    {
        if ($this->userSubscriptionManager->hasActiveSubscription($tg->getTelegram()->getMessengerUser())) {
            return $this->subscriptions($tg);
        }

        return $tg->startConversation(GetFeedbackPremiumTelegramConversation::class)->null();
    }

    public function subscriptions(TelegramAwareHelper $tg): null
    {
        $this->subscriptionsChatSender->sendFeedbackSubscriptions($tg);

        return $tg->startConversation(ChooseFeedbackActionTelegramConversation::class)->null();
    }

    public function acceptPayment(TelegramPayment $payment, TelegramAwareHelper $tg): void
    {
        $userSubscription = $this->userSubscriptionManager->createByTelegramPayment($payment);

        $tg->replyOk('feedbacks.reply.payment.ok', [
            'plan' => $tg->trans(sprintf('feedbacks.subscription_plan.%s', $userSubscription->getSubscriptionPlan()->name)),
            'expire_at' => $userSubscription->getExpireAt()->format($tg->trans('datetime_format', domain: null)),
        ]);

        // todo: show buttons (or continue active conversation)

        $tg->stopConversations()->startConversation(ChooseFeedbackActionTelegramConversation::class);
    }

    public function country(TelegramAwareHelper $tg): null
    {
        return $tg->startConversation(ChooseFeedbackCountryTelegramConversation::class)->null();
    }

    public function restart(TelegramAwareHelper $tg): null
    {
        $tg->stopConversations()->replyOk('feedbacks.reply.restart.ok');

        return $this->start($tg);
    }

    public function fallback(TelegramAwareHelper $tg): null
    {
        return $tg->replyWrong()->startConversation(ChooseFeedbackActionTelegramConversation::class)->null();
    }
}