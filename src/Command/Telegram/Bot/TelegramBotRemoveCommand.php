<?php

declare(strict_types=1);

namespace App\Command\Telegram\Bot;

use App\Exception\Telegram\Bot\TelegramBotNotFoundException;
use App\Repository\Telegram\Bot\TelegramBotRepository;
use App\Service\Telegram\Bot\TelegramBotRemover;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TelegramBotRemoveCommand extends Command
{
    public function __construct(
        private readonly TelegramBotRepository $repository,
        private readonly TelegramBotRemover $remover,
        private readonly EntityManagerInterface $entityManager,
    )
    {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'Telegram Username')
            ->setDescription('Remove telegram bot')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $username = $input->getArgument('username');
        $bot = $this->repository->findOneByUsername($username);

        if ($bot === null) {
            throw new TelegramBotNotFoundException($username);
        }

        $this->remover->removeTelegramBot($bot);
        $this->entityManager->flush();

        $io->newLine();
        $io->success(sprintf('"%s" Telegram bot has been removed', $bot->getUsername()));

        return Command::SUCCESS;
    }
}