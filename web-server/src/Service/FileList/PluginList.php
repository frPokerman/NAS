<?php

namespace App\Service\FileList;

use App\Exception\ResourceNotFound;
use App\Service\FileList\BaseList;
use Symfony\Bundle\FrameworkBundle\Routing\Attribute\AsRoutingConditionService;

#[AsRoutingConditionService(alias: 'plugin_route_validator')]
class PluginList extends BaseList
{
    const string EXTENSION = '.php';

    public function __construct()
    {
        parent::__construct('/../src/Plugin/');
    }
    
    protected function match(string $filename, string ...$parent): bool
    {
        return str_ends_with($filename, self::EXTENSION);
    }

    protected function transform(string $filename, string ...$parent): string
    {
        return strtolower(substr($filename, 0, - strlen(self::EXTENSION)));
    }

    protected function on_directory_not_found(string $directory): string
    {
        throw new ResourceNotFound('plugin folder', $directory);
    }

    public function get_plugin_list(): array
    {
        // TODO: filter plugins only without the base (in function match) and map each plugin class to its id (in function transform)
        return parent::get_files();
    }
}