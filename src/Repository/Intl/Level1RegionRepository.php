<?php

declare(strict_types=1);

namespace App\Repository\Intl;

use App\Entity\Intl\Level1Region;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Level1Region>
 *
 * @method Level1Region|null find($id, $lockMode = null, $lockVersion = null)
 * @method Level1Region|null findOneBy(array $criteria, array $orderBy = null)
 * @method Level1Region[]    findAll()
 * @method Level1Region[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class Level1RegionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Level1Region::class);
    }


    public function findOneByCountryAndName(string $countryCode, string $name): ?Level1Region
    {
        return $this->findOneBy([
            'countryCode' => $countryCode,
            'name' => $name,
        ]);
    }

    /**
     * @param string $countryCode
     * @return Level1Region[]
     */
    public function findByCountry(string $countryCode): array
    {
        return $this->findBy([
            'countryCode' => $countryCode,
        ]);
    }
}
