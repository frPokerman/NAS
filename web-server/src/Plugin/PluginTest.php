<?php

namespace App\Plugin;

use App\Attribute\Plugin;
use App\Plugin\BasePlugin;
use App\Plugin\Settings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedJsonResponse;
use Symfony\Component\Routing\Attribute\Route;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

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

    #[Route('/test/chunk/init')]
    public function chunking_test_init(Request $request, Filesystem $filesystem): StreamedJsonResponse
    {
        $temp = sys_get_temp_dir();
        $file = $filesystem->tempnam($temp, 'upload');
    
        return new StreamedJsonResponse(array(
            'upload_id' => basename($file)
        ));
    }

    #[Route('/test/chunk/upload')]
    public function chunking_test_upload(Request $request, Filesystem $filesystem): StreamedJsonResponse
    {
        $data = $request->getContent(true);

        $chunk_id = $request->headers->get('X-Chunk-Number');
        $upload_id = $request->headers->get('X-File-Id');
        
        $file = Path::join(sys_get_temp_dir(), $request);

        return new StreamedJsonResponse(array(
            
        ));
    }
}