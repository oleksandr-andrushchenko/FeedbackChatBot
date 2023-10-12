<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Location;
use App\Exception\TimezoneGeocodeFailedException;

interface TimezoneGeocoderInterface
{
    /**
     * @param Location $location
     * @param int|null $timestamp
     * @return string
     * @throws TimezoneGeocodeFailedException
     */
    public function geocodeTimezone(Location $location, int $timestamp = null): string;
}