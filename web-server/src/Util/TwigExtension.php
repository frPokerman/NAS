<?php

namespace App\Util;

use UnitEnum;
use Twig\Attribute\AsTwigTest;

class TwigExtension
{
    #[AsTwigTest('enum')]
    public function is_enum(mixed $value): bool
    {
        return $value instanceof UnitEnum;
    }
}