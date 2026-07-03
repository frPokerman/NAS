<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedJsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Plugin\AbstractPlugin;
use App\Plugin\PluginTest;
use App\Service\FileList\ConfigList;

class Core extends AbstractController
{
    #[Route('/api/plugins/list', name: 'plugins_list')]
    public function list_plugins(ConfigList $config): StreamedJsonResponse
    {
        return new StreamedJsonResponse($config->filter_plugins());
    }
}