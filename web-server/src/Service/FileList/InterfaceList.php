<?php

namespace App\Service\FileList;

use App\Exception\ResourceNotFound;
use App\Service\FileList\BaseList;
use Symfony\Bundle\FrameworkBundle\Routing\Attribute\AsRoutingConditionService;

#[AsRoutingConditionService(alias: 'interface_route_validator')]
class InterfaceList extends BaseList
{
    const string MAIN_FILENAME = 'main.html.twig';

    public function __construct()
    {
        parent::__construct('/../templates/');
    }
    
    protected function match(string $filename, string ...$parent): bool
    {
        // TODO: Also compare with the first parent with the (API) plugin list
        return $filename == self::MAIN_FILENAME && count($parent) > 0;
    }

    protected function join(string $filename, string ...$parent): string
    {
        return $parent[0];
    }

    protected function transform(string $filename, string ...$parent): string
    {
        return strtolower($parent[0]);
    }

    protected function on_directory_not_found(string $directory): string
    {
        throw new ResourceNotFound('interface folder', $directory);
    }

    public function validate(string $plugin_name): bool
    {
        return parent::contains($plugin_name);
    }
}