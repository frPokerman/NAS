<?php

namespace App\Plugin;

use App\Data\KVMap;
use App\Exception\BaseException;
use App\Exception\ResourceNotFound;
use App\Plugin\BasePlugin;
use App\Service\FileList\ConfigList;
use App\Service\YAMLParser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Attribute\AsTwigFunction;

// TODO: Completely replace Symfony's YAML by the YAMLParser service
use Symfony\Component\Yaml\Yaml;

class Settings
{
    const string FILE_SEPARATOR = ':';
    
    protected KVMap $map;

    public function __construct(
        protected YAMLParser $parser,
        protected ConfigList $config
    )
    {
        $this->map = new KVMap();
    }

    #[AsTwigFunction('list_plugin_configs')]
    public function filter_plugins(): array
    {
        $list = array();
        foreach ($this->config->get_list() as $config_name)
        {
            $p = explode(ConfigList::SEPARATOR, $config_name);
            // Filter plugins only, and ignore plugins already included
            if ($p[0] == 'plugins' && count($p) > 1 && !in_array($p[1], $list))
            {
                $list[] = $p[1];
            }
        }
        
        return $list;
    }

    protected function prefix(array $arr, string $prefix = self::FILE_SEPARATOR): array
    {
        return array_map(function($p) use ($prefix)
        {
            return $prefix.$p;
        }, $arr);
    }

    protected function unprefix(array $arr, string $prefix = self::FILE_SEPARATOR): array
    {
        $new_arr = array();
        foreach ($arr as $p => $v)
        {
            $key = str_starts_with($p, $prefix) ? substr($p, strlen($prefix)) : $p;
            $child = is_array($v) ? $this->unprefix($v, $prefix) : $v;
            $new_arr[$key] = $child;
        }

        return $new_arr;
    }

    protected function read_config_files(string ...$config_path): void
    {
        foreach ($this->config->list_files_for(...$config_path) as $file_path)
        {
            $map_path = $this->prefix($file_path);
            foreach ($this->parser->read($this->config->resolve_file(...$file_path), true) as $key => $value)
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
        
        file_put_contents($this->config->resolve_file(...$file_path), Yaml::dump($document));
    }

    protected function check_config(string $config_path): array
    {
        $p = explode(self::FILE_SEPARATOR, $config_path, 2);
        if (count($p) < 1) return array($p, array());

        $file = explode(ConfigList::SEPARATOR, $p[0]);
        if (!$this->map->contains(...$this->prefix($file)))
        {
            $this->read_config_files(...$file);
        }

        if (count($p) == 1) return array($file, array());
        
        $config = explode(ConfigList::SEPARATOR, $p[1]);
        return array($file, $config);
    }

    public function get(string $config_path): mixed
    {
        $path = $this->check_config($config_path);
        
        try
        {
            $result = $this->map->develop_path(...array_merge($this->prefix($path[0]), $path[1]));
            return is_array($result) ? $this->unprefix($result) : $result;
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

    public function join_config_path(string ...$config_path): string
    {
        $count = count($config_path);
        if ($count < 1) return '';

        $separated = false;
        $str = $config_path[0];
        for ($i = 1; $i < $count; $i++)
        {
            $pos = strpos($config_path[$i], self::FILE_SEPARATOR);
            if ($pos === 0 && !$separated)
            {
                $str .= $config_path[$i];
                $separated = true;
                continue;
            }
            else if ($pos !== false)
            {
                if ($separated)
                {
                    throw new BaseException(
                        'Too many file separators ("' .
                        self::FILE_SEPARATOR . '") in configuration path "' .
                        implode(', ', $config_path) . '". Expected maximum 1.'
                    );
                }

                $separated = true;
            }
            else if ($i == $count - 1)
            {
                $str .= self::FILE_SEPARATOR . $config_path[$i];
                continue;
            }
            
            $str .= $this->config::SEPARATOR . $config_path[$i];
        }
        
        return $str;
    }

    #[AsTwigFunction('plugin_config')]
    public function get_plugin_config(string $plugin_name): array
    {
        return $this->get('plugins' . $this->config::SEPARATOR . $plugin_name);
    }
}