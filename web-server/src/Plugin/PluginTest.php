<?php

namespace App\Plugin;

use App\Plugin\BasePlugin;
use App\Service\FileList\ConfigList;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\StreamedJsonResponse;

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

    #[Route('/test/config')]
    public function list_config(ConfigList $config): StreamedJsonResponse
    {
        return new StreamedJsonResponse($config->get('plugins'));
    }
}