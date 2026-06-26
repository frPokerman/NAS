<?php

namespace App\Controller\Core;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class Plugins
{
    #[Route('/api/core/plugins/count')]
    public function number(): Response
    {
        $number = random_int(0, 100);

        return new Response(
            '<html><body>Plugins count: ' . $number . '</body></html>'
        );
    }
}