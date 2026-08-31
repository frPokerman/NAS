<?php

namespace App\Plugin;

use App\Attribute\Plugin;
use App\Plugin\BasePlugin;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\EventStreamResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ServerEvent;
use Symfony\Component\HttpFoundation\StreamedJsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Plugin('files')]
class Files extends BasePlugin
{
    public const string UPLOADS_FOLDER = 'UserUploads';

    private string $register;
    
    public function construct(): void
    {
        $this->register = Path::join($this->config('home'), 'file-register.csv');
    }

    #[Route('/files/browse/{file_path}', name: 'files_browse', requirements: ['file_path' => '.+'])]
    public function browseFile(string $file_path): BinaryFileResponse
    {
        return new BinaryFileResponse(Path::join($this->config('home'), $file_path));
    }

    #[Route('/files/upload', methods: [ 'GET' ])]
    public function promptFile(): Response
    {
        return $this->render($this->get_id() . '/upload.html.twig');
    }

    #[Route('/files/upload', methods: [ 'POST' ])]
    public function uploadFile(Request $request, Filesystem $filesystem): EventStreamResponse
    {
        // TODO: (security tuto) https://symfonycasts.com/screencast/symfony-uploads/upload-request

        return new EventStreamResponse(function () use ($request, $filesystem): iterable
        {
            $count = $request->request->get('count', 1);

            yield new ServerEvent('Found ' . $count . ' files.');

            if ($count < 1 || !$request->files->has('file0')) throw new Exception('Missing file(s) to upload.');

            $success = 0;
            $locations = array();

            for ($i = 0; $i < $count; $i++)
            {
                yield new ServerEvent('Processing file ' . ($i + 1) . '/' . $count);

                $file = $request->files->get('file' . $i);
                if (!$file)
                {
                    yield new ServerEvent('File not received, skipping.');
                    continue;
                }
    
                yield new ServerEvent('Current file is: "' . $file->getClientOriginalName() . '".');
    
                $filename = uniqid() . '.' . $file->guessExtension();
                $destination = Path::join($this->config('home'),  self::UPLOADS_FOLDER);

                $file->move($destination, $filename);

                $filesystem->appendToFile($this->register, $filename . ';' . $file->getClientOriginalName() . "\n");
                
                $success++;
                $locations[$i] = $this->generateUrl('files_browse', [
                    'file_path' => self::UPLOADS_FOLDER . '/' . $filename
                ]);

                yield new ServerEvent('Saved file at: ' . $destination . '/' . $filename);
            }
    
            if ($success == 0) throw new Exception('Expected ' . $count . ' file(s), found none.');

            yield new ServerEvent('Uploading complete.');
        });      
    }
}