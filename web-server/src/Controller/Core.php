<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedJsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Plugin\AbstractPlugin;
use App\Plugin\PluginTest;
use App\Service\FileList\PluginList;

class Core extends AbstractController
{
    #[Route('/api/plugins/list', name: 'plugins_list')]
    public function list_plugins(PluginList $list): StreamedJsonResponse
    {
        return new StreamedJsonResponse($list->get_plugin_list());
    }

    #[Route('/api/test')]
    public function api_test(\App\Service\FileList\ConfigList $config): StreamedJsonResponse
    {
        return new StreamedJsonResponse($config->get('plugins'));
    }
}