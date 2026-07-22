<?php

namespace App\Plugin;

use App\Attribute\Plugin as PluginAttribute;
use App\Plugin\Settings;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

// Excluded from services in services.yaml
class BasePlugin extends AbstractController
{
    protected string $id;

    function __construct(protected Settings $config)
    {
        // TODO: What if no #[Plugin] attribute given?

        $ref_class = new ReflectionClass($this::class);
        foreach ($ref_class->getAttributes(PluginAttribute::class) as $ref_attribute)
        {
            $attribute = $ref_attribute->newInstance();
            $this->id = $attribute->plugin_id;
            break;
        }
        
        $this->construct();
    }
    
    public function construct(): void
    {
        // May be overriden
    }

    public function get_id(): string
    {
        return $this->id;
    }

    protected function config(string $key, mixed $value = null)
    {
        $path = $this->config->join_config_path('plugins', $this->get_id(), $key);
        if (is_null($value))
        {
            return $this->config->get($path);
        }

        $this->config->set($path, $value);
    }
}