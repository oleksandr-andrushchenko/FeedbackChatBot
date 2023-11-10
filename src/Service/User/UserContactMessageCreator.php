<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\User\UserContactMessage;
use App\Exception\ValidatorException;
use App\Message\Event\ActivityEvent;
use App\Service\IdGenerator;
use App\Transfer\User\UserContactMessageTransfer;
use App\Service\Validator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class UserContactMessageCreator
{
    public function __construct(
        private readonly Validator $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly IdGenerator $idGenerator,
        private readonly MessageBusInterface $eventBus,
    )
    {
    }

    /**
     * @param UserContactMessageTransfer $transfer
     * @return UserContactMessage
     * @throws ValidatorException
     */
    public function createUserContactMessage(UserContactMessageTransfer $transfer): UserContactMessage
    {
        $this->validator->validate($transfer);

        $message = new UserContactMessage(
            $this->idGenerator->generateId(),
            $transfer->getMessengerUser(),
            $transfer->getUser(),
            $transfer->getText(),
            $transfer->getTelegramBot()
        );
        $this->entityManager->persist($message);

        $this->eventBus->dispatch(new ActivityEvent(entity: $message));

        return $message;
    }
}