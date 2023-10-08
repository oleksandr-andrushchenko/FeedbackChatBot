<?php

declare(strict_types=1);

namespace App\Command\Telegram;

use App\Enum\Telegram\TelegramGroup;
use App\Exception\Telegram\TelegramGroupNotFoundException;
use App\Repository\Telegram\TelegramChannelRepository;
use App\Service\Telegram\TelegramChannelInfoProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TelegramChannelShowAllCommand extends Command
{
    public function __construct(
        private readonly TelegramChannelRepository $repository,
        private readonly TelegramChannelInfoProvider $infoProvider,
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
            ->addOption('group', mode: InputOption::VALUE_REQUIRED, description: 'Telegram Group (inner name)')
            ->setDescription('Show all telegram channels info')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $groupName = $input->getOption('group');
        $group = null;

        if ($groupName !== null) {
            $group = TelegramGroup::fromName($groupName);

            if ($group === null) {
                throw new TelegramGroupNotFoundException($groupName);
            }
        }

        $channels = $this->repository->findAll();

        $table = [];
        $index = 0;

        foreach ($channels as $channel) {
            if ($group !== null && $channel->getGroup() !== $group) {
                continue;
            }

            $table[] = array_merge(
                [
                    '#' => $index + 1,
                ],
                $this->infoProvider->getTelegramChannelInfo($channel)
            );

            $index++;
        }

        if (count($table) === 0) {
            $io->success('No telegram channels have been found');
        } else {
            $io->createTable()
                ->setHeaders(array_keys($table[0]))
                ->setRows($table)
                ->render()
            ;

            $io->newLine();
            $io->success('Telegram channels info have been shown');
        }

        return Command::SUCCESS;
    }
}