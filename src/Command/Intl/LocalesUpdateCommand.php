<?php

declare(strict_types=1);

namespace App\Command\Intl;

use App\Service\Intl\LocaleTranslationsProviderInterface;
use App\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use RuntimeException;

class LocalesUpdateCommand extends Command
{
    public function __construct(
        private readonly LocaleTranslationsProviderInterface $translationsProvider,
        private readonly string $translationTargetFile,
        private readonly array $supportedLocales,
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
            ->setDescription('Update locale translations')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function invoke(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->updateLocaleTranslations($io);
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->newLine();
        $io->success('Languages have been updated');

        return Command::SUCCESS;
    }

    private function updateLocaleTranslations(SymfonyStyle $io): void
    {
        $translations = $this->translationsProvider->getLocaleTranslations();

        if ($translations === null) {
            throw new RuntimeException('Unable to fetch locale translations');
        }

        foreach ($translations as $locale => $data) {
            if (!in_array($locale, $this->supportedLocales, true)) {
                continue;
            }

            $yaml = '';
            foreach ($data as $language => $translation) {
                $yaml .= sprintf("%s: %s\n", $language, $translation);
            }

            $written = file_put_contents(str_replace('{locale}', $locale, $this->translationTargetFile), $yaml);

            if ($written === false) {
                throw new RuntimeException(sprintf('Unable to write "%s" locale translation', $locale));
            }

            $io->note(json_encode([
                'locale' => $locale,
                'translations' => array_keys($data),
            ]));
        }
    }
}