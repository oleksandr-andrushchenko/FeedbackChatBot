<?php

declare(strict_types=1);

namespace App\Service\Address;

use App\Entity\Address\Address;
use App\Entity\Address\Level1Region;
use App\Repository\Address\Level1RegionRepository;
use Doctrine\ORM\EntityManagerInterface;

class Level1RegionUpserter
{
    public function __construct(
        private readonly Level1RegionRepository $repository,
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    public function upsertLevel1RegionByAddress(Address $address): Level1Region
    {
        return $this->upsertLevel1RegionByCountryAndName(
            $address->getCountry(),
            $address->getAdministrativeAreaLevel1()
        );
    }

    public function upsertLevel1RegionByCountryAndName(string $countryCode, string $name): Level1Region
    {
        $level1Region = $this->repository->findOneByCountryAndName($countryCode, $name);

        if ($level1Region === null) {
            $level1Region = new Level1Region(
                $countryCode,
                $name,
            );
            $this->entityManager->persist($level1Region);
            $this->entityManager->flush();
        }

        return $level1Region;
    }
}