<?php

namespace App\Plugin;

use App\Attribute\Plugin;
use App\Exception\ResourceNotFound;
use App\Plugin\BasePlugin;
use App\Service\FileList\ConfigList;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

#[Plugin('interface')]
class WebInterface extends BasePlugin
{
    #[Route('/{plugin_id}', condition: "service('interface_route_validator').validate(params['plugin_id'])")]
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
                throw new ResourceNotFound('plugin', $plugin_id);   
            }
            else
            {
                throw $error;
            }
        }
    }
}