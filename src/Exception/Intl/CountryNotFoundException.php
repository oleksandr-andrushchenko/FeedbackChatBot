<?php

declare(strict_types=1);

namespace App\Exception\Intl;

use App\Exception\Exception;
use Throwable;

class CountryNotFoundException extends Exception
{
    public function __construct(string $country, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('"%s" country has not been found', $country), $code, $previous);
    }
}
