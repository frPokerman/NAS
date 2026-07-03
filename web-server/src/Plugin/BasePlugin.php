<?php

namespace App\Plugin;

use App\Service\FileList\ConfigList;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

// Excluded from services in services.yaml
class BasePlugin extends AbstractController
{
    function __construct(
        protected string $id,
        protected ConfigList $config
    ) { }

    public function get_id(): string
    {
        return $this->id;
    }

    protected function config(string $key, $value = null)
    {
        $path = 'plugins' . ConfigList::SEPARATOR . $this->get_id() . ConfigList::FILE_SEPARATOR . $key;
        if (is_null($value))
        {
            return $this->config->get($path);
        }

        $this->config->set($path, $value);
    }
}