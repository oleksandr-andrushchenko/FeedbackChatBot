<?php

declare(strict_types=1);

namespace App\Service\Telegram\Channel;

use App\Entity\ImportResult;
use App\Entity\Location;
use App\Enum\Telegram\TelegramBotGroupName;
use App\Exception\AddressGeocodeFailedException;
use App\Exception\TimezoneGeocodeFailedException;
use App\Repository\Telegram\Channel\TelegramChannelRepository;
use App\Service\Address\Level1RegionProvider;
use App\Service\CsvFileWalker;
use App\Service\Intl\CountryProvider;
use App\Service\Intl\LocaleProvider;
use App\Transfer\Telegram\TelegramChannelTransfer;
use Doctrine\ORM\EntityManagerInterface;

class TelegramChannelImporter
{
    public function __construct(
        private readonly Level1RegionProvider $level1RegionProvider,
        private readonly TelegramChannelRepository $repository,
        private readonly TelegramChannelCreator $creator,
        private readonly TelegramChannelUpdater $updater,
        private readonly TelegramChannelRemover $remover,
        private readonly CountryProvider $countryProvider,
        private readonly LocaleProvider $localeProvider,
        private readonly CsvFileWalker $walker,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $stage,
    )
    {
    }

    public function importTelegramChannels(string $filename, callable $logger = null): ImportResult
    {
        $result = new ImportResult();
        $logger = $logger ?? fn (string $message) => null;

        $channels = $this->repository->findAll();
        $usernames = $this->getUsernames($filename);

        foreach ($channels as $channel) {
            if (!in_array($channel->getUsername(), $usernames, true) && !$this->remover->telegramChannelRemoved($channel)) {
                $this->remover->removeTelegramChannel($channel);
                $logger(sprintf('%s: [OK] deleted', $channel->getUsername()));
                $result->incDeletedCount();
            }
        }

        $this->entityManager->flush();

        $this->walk($filename, $result, $logger, function (array $data, ImportResult $result, callable $logger): void {
            $transfer = new TelegramChannelTransfer($data['username']);

            $group = TelegramBotGroupName::fromName($data['group']);

            if ($group === null) {
                $logger(sprintf('%s: [FAIL] group - "%s" not found', $transfer->getUsername(), $data['group']));
                $result->incFailedCount();

                return;
            }

            $country = $this->countryProvider->getCountry($data['country']);

            if ($country === null) {
                $logger(sprintf('%s: [FAIL] country - "%s" not found', $transfer->getUsername(), $data['country']));
                $result->incFailedCount();

                return;
            }

            $locale = $this->localeProvider->getLocale($data['locale']);

            if ($locale === null) {
                $logger(sprintf('%s: [FAIL] locale - "%s" not found', $transfer->getUsername(), $data['locale']));
                $result->incFailedCount();

                return;
            }

            $addressComponents = [
                'country',
                'level_1_region',
            ];

            $level1Region = null;

            $partiallyNonEmpty = !empty($data['location_latitude']) || !empty($data['location_longitude']) || !empty($data['location_address_component']);

            if ($partiallyNonEmpty) {
                $nonEmptyLocation = !empty($data['location_latitude']) && !empty($data['location_longitude']) && !empty($data['location_address_component']);

                if (!$nonEmptyLocation) {
                    $logger(sprintf('%s: [FAIL] locations - partially empty', $transfer->getUsername()));
                    $result->incFailedCount();

                    return;
                }

                $location = new Location($data['location_latitude'], $data['location_longitude']);
                $highestAddressComponent = $data['location_address_component'];

                $addressComponentPosition = array_search($highestAddressComponent, $addressComponents);

                if ($addressComponentPosition >= array_search('level_1_region', $addressComponents)) {
                    try {
                        $level1Region = $this->level1RegionProvider->getLevel1RegionByLocation($location);
                    } catch (AddressGeocodeFailedException|TimezoneGeocodeFailedException $exception) {
                        $logger(sprintf('%s: [FAIL] level_1_region - %s', $transfer->getUsername(), $exception->getMessage()));
                        $result->incFailedCount();

                        return;
                    }
                }
            }

            if ($level1Region === null && !empty($data['level_1_region_name'])) {
                $level1Region = $this->level1RegionProvider->getLevel1RegionByCountryAndName(
                    $country->getCode(),
                    $data['level_1_region_name'],
                    empty($data['level_1_region_timezone']) ? null : $data['level_1_region_timezone']
                );
            }

            $transfer
                ->setGroup($group)
                ->setName($data['name'])
                ->setCountry($country)
                ->setLocale($locale)
                ->setLevel1Region($level1Region)
                ->setPrimary($data['primary'] === '1')
            ;

            $channel = $this->repository->findAnyOneByUsername($transfer->getUsername());

            if ($channel === null) {
                $this->creator->createTelegramChannel($transfer);
                $logger(sprintf('%s: [OK] created', $transfer->getUsername()));
                $result->incCreatedCount();
            } else {
                $this->updater->updateTelegramChannel($channel, $transfer);
                $logger(sprintf('%s: [OK] updated', $transfer->getUsername()));
                $result->incUpdatedCount();

                if ($this->remover->telegramChannelRemoved($channel)) {
                    $this->remover->undoTelegramChannelRemove($channel);
                    $logger(sprintf('%s: [OK] restored', $transfer->getUsername()));
                    $result->incRestoredCount();
                }
            }
        });

        return $result;
    }

    private function getUsernames(string $filename): array
    {
        $usernames = [];

        $this->walk($filename, null, null, static function (array $data) use (&$usernames): void {
            $usernames[] = $data['username'];
        });

        return $usernames;
    }

    private function walk(string $filename, ?ImportResult $result, ?callable $logger, callable $func): void
    {
        $mandatoryColumns = [
            'skip',
            'group',
            'username',
            'name',
            'stage',
            'country',
            'locale',
            'primary',
            'location_latitude',
            'location_longitude',
            'location_address_component',
            'level_1_region_name',
            'level_1_region_timezone',
        ];

        $this->walker->walk($filename, function (array $data) use ($result, $logger, $func): void {
            if ($data['stage'] !== $this->stage) {
//                $logger && $logger(sprintf('%s: [OK] skipped - stage filter', $data['username']));
                $result && $result->incSkippedCount();

                return;
            }

            if ($data['skip'] === '1') {
//                $logger && $logger(sprintf('%s: [OK] skipped - skip filter', $data['username']));
                $result && $result->incSkippedCount();

                return;
            }

            $func($data, $result, $logger);
        }, mandatoryColumns: $mandatoryColumns);
    }
}