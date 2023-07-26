<?php

declare(strict_types=1);

namespace App\Command\Intl;

use App\Service\Intl\CurrenciesFetcherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Throwable;
use RuntimeException;

class CurrenciesUpdateCommand extends Command
{
    public function __construct(
        private readonly CurrenciesFetcherInterface $fetcher,
        private readonly NormalizerInterface $normalizer,
        private readonly string $destinationPath,
    )
    {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setDescription('Update latest currencies data')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $currencies = $this->fetcher->fetchCurrencies();

            if ($currencies === null) {
                throw new RuntimeException('Unable to fetch currencies');
            }

            $json = json_encode(array_map(fn ($currency) => $this->normalizer->normalize($currency), $currencies));

            $written = file_put_contents($this->destinationPath, $json);

            if ($written === false) {
                throw new RuntimeException('Unable to write currencies');
            }
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->note($json);

        $io->newLine();
        $io->success('Currencies have been updated');

        return Command::SUCCESS;
    }
}