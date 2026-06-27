<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Exception\PluginNotFoundException;
use Twig\Error\LoaderError;

class GenericPlugin extends AbstractController
{
    #[Route('/{plugin_id}')]
    public function get_main(string $plugin_id): Response
    {
        // TODO : Replace that with an 'if file exists' condition
        try
        {
            // TODO : Maybe parse query params ? here gives it to Twig as second parameter
            return $this->render($plugin_id . '/main.html.twig');
        }
        catch (LoaderError $error)
        {
            // this is why I need a clean 'if file exists':
            //   actually checks if the template causing an issue is the very one requested by '$this->render'
            if (str_starts_with(substr($error->getMessage(), 25), $plugin_id . '/main.html.twig'))
            {
                throw new PluginNotFoundException($plugin_id);   
            }
            else
            {
                throw $error;
            }
        }
    }
}