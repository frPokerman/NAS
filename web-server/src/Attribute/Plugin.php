<?php

namespace App\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Plugin
{
    public function __construct(
        public string $plugin_id
    ) { }
}