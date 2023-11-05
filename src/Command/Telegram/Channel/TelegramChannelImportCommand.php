<?php

declare(strict_types=1);

namespace App\Command\Telegram\Channel;

use App\Entity\ImportResult;
use App\Service\Doctrine\DryRunner;
use App\Service\Telegram\Channel\TelegramChannelImporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class TelegramChannelImportCommand extends Command
{
    public function __construct(
        private readonly string $dataDir,
        private readonly TelegramChannelImporter $telegramChannelImporter,
        private readonly DryRunner $dryRunner,
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
            ->addArgument('file', InputArgument::REQUIRED, 'Base Filename name to import')
            ->addOption('dry-run', mode: InputOption::VALUE_NONE, description: 'Dry run')
            ->setDescription('Import telegram channels')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $filename = $this->dataDir . '/' . $input->getArgument('file');
        $dryRun = $input->getOption('dry-run');

        if (!$dryRun) {
            $confirmed = $io->askQuestion(
                new ConfirmationQuestion(sprintf('Are you sure you want to import "%s" Telegram channels file?', $filename), true)
            );

            if (!$confirmed) {
                $io->warning(
                    sprintf('"%s" Telegram channels file import has been cancelled', $filename)
                );

                return Command::SUCCESS;
            }
        }

        $logger = static fn (string $message) => $io->note($message);
        $func = fn () => $this->telegramChannelImporter->importTelegramChannels($filename, $logger);

        if ($dryRun) {
            $result = $this->dryRunner->dryRun($func, readUncommitted: true);
        } else {
            $result = $this->entityManager->wrapInTransaction($func);
        }

        /** @var ImportResult $result */

        $io->success(
            sprintf(
                'Telegram channels have been imported, created: %d, updated: %d, deleted: %d, restored: %d, unchanged: %d, skipped: %d, failed: %d',
                $result->getCreatedCount(),
                $result->getUpdatedCount(),
                $result->getDeletedCount(),
                $result->getRestoredCount(),
                $result->getUnchangedCount(),
                $result->getSkippedCount(),
                $result->getFailedCount(),
            )
        );

        return Command::SUCCESS;
    }
}