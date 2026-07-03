<?php

namespace App\Exception;

use Exception;

class BaseException extends Exception
{
    function __construct(string $message = 'An unexpected internal error occured.', ...$args)
    {
        parent::__construct($message, ...$args);
    }
}