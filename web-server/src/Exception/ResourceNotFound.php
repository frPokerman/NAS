<?php

namespace App\Exception;

use App\Exception\BaseException;

class ResourceNotFound extends BaseException
{
    function __construct(string $resource, string $resource_name, ...$args)
    {
        parent::__construct('No ' . $resource . ' named "' . $resource_name . '".', ...$args);
    }
}