<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PluginNotFoundException extends NotFoundHTTPException
{
    function __construct($plugin_name, ...$args)
    {
        parent::__construct('No plugin named "' . $plugin_name . '"', ...$args);
    }
}