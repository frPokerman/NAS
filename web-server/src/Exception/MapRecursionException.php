<?php

namespace App\Exception;

use App\Exception\BaseException;

class MapRecursionException extends BaseException
{
    function __construct(
        string $message,
        protected array $path
    )
    {
        parent::__construct($message);
    }

    public function get_path(): array
    {
        return $this->path;
    }
}