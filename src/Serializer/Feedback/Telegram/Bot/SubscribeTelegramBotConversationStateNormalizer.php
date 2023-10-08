<?php

declare(strict_types=1);

namespace App\Serializer\Feedback\Telegram\Bot;

use App\Entity\Feedback\FeedbackSubscriptionPlan;
use App\Entity\Feedback\Telegram\Bot\SubscribeTelegramBotConversationState;
use App\Repository\Telegram\Bot\TelegramBotPaymentMethodRepository;
use App\Service\Intl\CurrencyProvider;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SubscribeTelegramBotConversationStateNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function __construct(
        private readonly NormalizerInterface $baseConversationStateNormalizer,
        private readonly DenormalizerInterface $baseConversationStateDenormalizer,
        private readonly NormalizerInterface $subscriptionPlanNormalizer,
        private readonly DenormalizerInterface $subscriptionPlanDenormalizer,
        private readonly TelegramBotPaymentMethodRepository $paymentMethodRepository,
        private readonly CurrencyProvider $currencyProvider,
    )
    {
    }

    /**
     * @param SubscribeTelegramBotConversationState $object
     * @param string|null $format
     * @param array $context
     * @return array
     * @throws ExceptionInterface
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        return array_merge($this->baseConversationStateNormalizer->normalize($object, $format, $context), [
            'currency' => $object->getCurrency() === null ? null : $object->getCurrency()->getCode(),
            'currency_step' => $object->isCurrencyStep(),
            'subscription_plan' => $object->getSubscriptionPlan() === null ? null : $this->subscriptionPlanNormalizer->normalize($object->getSubscriptionPlan(), $format, $context),
            'payment_method' => $object->getPaymentMethod() === null ? null : $object->getPaymentMethod()->getId(),
            'payment_method_step' => $object->isPaymentMethodStep(),
        ]);
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof SubscribeTelegramBotConversationState;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): SubscribeTelegramBotConversationState
    {
        /** @var SubscribeTelegramBotConversationState $object */
        $object = $this->baseConversationStateDenormalizer->denormalize($data, $type, $format, $context);

        $object
            ->setCurrency(isset($data['currency']) ? $this->currencyProvider->getCurrency($data['currency']) : null)
            ->setCurrencyStep($data['currency_step'] ?? null)
            ->setSubscriptionPlan(isset($data['subscription_plan']) ? $this->subscriptionPlanDenormalizer->denormalize($data['subscription_plan'], FeedbackSubscriptionPlan::class, $format, $context) : null)
            ->setPaymentMethod(isset($data['payment_method']) ? $this->paymentMethodRepository->find($data['payment_method']) : null)
            ->setPaymentMethodStep($data['payment_method_step'] ?? null)
        ;

        return $object;
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return is_array($data) && $type === SubscribeTelegramBotConversationState::class;
    }
}