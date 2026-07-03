<?php

namespace App\Plugin;

use App\Plugin\BasePlugin;
use App\Service\FileList\ConfigList;

class PluginTest extends BasePlugin
{
    public function __construct(ConfigList $config)
    {
        parent::__construct('test', $config);
    }

    public function calculate(float $arg): string
    {
        $value = $this->config('factor');
        return $arg . ' x ' . $value . ' = ' . ($arg * $value);
    }

    public function increment(): void
    {
        $this->config('factor', $this->config('factor') + 1);
    }
}