<?php

namespace App\Plugin;

use App\Attribute\Plugin;
use App\Plugin\BasePlugin;
use App\Plugin\Settings;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\StreamedJsonResponse;

#[Plugin('test')]
class PluginTest extends BasePlugin
{
    private string $test1_property;
    public function construct(): void
    {
        $this->test1_property = 'abc:timestamp';
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
    public function list_config(Settings $config): StreamedJsonResponse
    {
        return new StreamedJsonResponse($config->get('plugins'));
    }

    #[Route('/test/test1')]
    public function config_test1(): StreamedJsonResponse
    {
        return new StreamedJsonResponse(array(
            'value' => $this->config($this->test1_property)->getvalue()
        ));
    }
}