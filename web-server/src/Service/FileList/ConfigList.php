<?php

namespace App\Service\FileList;

use App\Exception\BaseException;
use App\Exception\ResourceNotFound;
use App\Service\FileList\BaseList;
use App\Util\KVMap;
use Symfony\Bundle\FrameworkBundle\Routing\Attribute\AsRoutingConditionService;
use Symfony\Component\Yaml\Yaml;
use Twig\Attribute\AsTwigFunction;

#[AsRoutingConditionService(alias: 'config_route_validator')]
class ConfigList extends BaseList
{
    const string SEPARATOR = '.';
    const string FILE_SEPARATOR = ':';
    const string EXTENSION = '.yaml';

    // protected $configs = array();
    protected $map;

    public function __construct()
    {
        parent::__construct('/../../config/');
        $this->map = new KVMap();
    }
    
    protected function match(string $filename, string ...$parent): bool
    {
        return str_ends_with($filename, self::EXTENSION);
    }

    protected function join(string $filename, string ...$parent): string
    {
        $parent[] = $filename;
        return implode(self::SEPARATOR, $parent);
    }

    protected function transform(string $filename, string ...$parent): string
    {
        return strtolower(substr($filename, 0, - strlen(self::EXTENSION)));
    }

    protected function on_directory_not_found(string $directory): string
    {
        throw new ResourceNotFound('configuration folder', $directory);
    }

    #[AsTwigFunction('list_plugin_configs')]
    public function filter_plugins(): array
    {
        $list = array();
        foreach (parent::get_files() as $config_name)
        {
            $p = explode(ConfigList::SEPARATOR, $config_name);
            if ($p[0] == 'plugins' && count($p) > 1 && !in_array($p[1], $list))
            {
                $list[] = $p[1];
            }
        }
        return $list;
    }

    protected function prefix(array $arr, string $prefix = self::FILE_SEPARATOR)
    {
        return array_map(function($p) use ($prefix)
        {
            return $prefix.$p;
        }, $arr);
    }

    protected function resolve_file(string ...$file_path): string
    {
        return realpath(
            $this->root .
            implode('/', $file_path) .
            self::EXTENSION
        );
    }

    protected function list_path_for(string ...$config_path): array
    {
        $last_file = end($config_path);
        $parent = array_slice($config_path, 0, -1);
        $list = array();
        foreach ($this->list_all(true, ...$parent) as $file)
        {
            if (str_starts_with($file, $last_file))
            {
                $list[] = array_merge($parent, explode(self::SEPARATOR, $file));
            }
        }
        return $list;
    }

    protected function read_config_files(string ...$config_path): void
    {
        foreach ($this->list_path_for(...$config_path) as $file_path)
        {
            $map_path = $this->prefix($file_path);
            foreach (Yaml::parseFile($this->resolve_file(...$file_path)) as $key => $value)
            {
                $this->map->assign_path($value, ...array_merge($map_path, array($key)));
            }
        }
    }

    protected function write_config_file(string ...$file_path): void
    {
        $document = array();

        foreach ($this->map->develop_path(...$this->prefix($file_path)) as $key => $value)
        {
            if (str_starts_with($key, self::FILE_SEPARATOR)) continue;

            $document[$key] = $value;
        }
        
        file_put_contents($this->resolve_file(...$file_path), Yaml::dump($document));
    }

    protected function check_config(string $config_path): array
    {
        $p = explode(self::FILE_SEPARATOR, $config_path);
                                                    // , 2);
        if (count($p) < 1) return array($p, array());

        $file = explode(self::SEPARATOR, $p[0]);
        if (!$this->map->contains(...$this->prefix($file)))
        {
            $this->read_config_files(...$file);
        }

        if (count($p) == 1) return array($file, array());
        
        $config = explode(self::SEPARATOR, $p[1]);
        return array($file, $config);
    }

    public function get(string $config_path): mixed
    {
        $path = $this->check_config($config_path);
        
        try
        {
            return $this->map->develop_path(...array_merge($this->prefix($path[0]), $path[1]));
        }
        catch (BaseException $e)
        {
            throw new ResourceNotFound('configuration', $config_path);
        }
    }

    public function set(string $config_path, mixed $value): void
    {
        $path = $this->check_config($config_path);

        try
        {
            $this->map->change_path($value, ...array_merge($this->prefix($path[0]), $path[1]));
        }
        catch (BaseException $e)
        {
            throw new ResourceNotFound('configuration', $config_path);
        }
        
        $this->write_config_file(...$path[0]);
    }

    #[AsTwigFunction('plugin_config')]
    public function get_config(string $plugin_name): array
    {
        return $this->get($this->join($plugin_name, 'plugins'));
    }
}