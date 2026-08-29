<?php

declare(strict_types=1);

namespace MuradyanVano\LexoRank\Exception;

use InvalidArgumentException;

/**
 * Base invalid-argument exception for the package.
 */
class LexoRankException extends InvalidArgumentException implements LexoRankExceptionInterface
{
}
