<?php

namespace App\Util;

use App\Data\Configuration;
use Twig\Attribute\AsTwigFunction;
use Twig\Attribute\AsTwigTest;

class TwigExtension
{
    #[AsTwigTest('config')]
    public function is_config(mixed $value): bool
    {
        return $value instanceof Configuration;
    }

    // May be moved to src/Data/Configuration.php
    #[AsTwigFunction('to_input_type')]
    public function type_to_input(string $type): string
    {
        switch ($type)
        {
            case 'string':
                return 'text';
            case 'integer':
            case 'double':
                return 'number';
            case 'boolean':
                return 'checkbox';
            default:
                return $type;
        }
    }
}