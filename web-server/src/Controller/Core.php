<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class Core
{
    #[Route('/api/core/plugins/count')]
    public function countPlugins(): Response
    {
        $number = 2;

        return new Response(
            '<html><body>Plugins count: ' . $number . '</body></html>'
        );
    }
}