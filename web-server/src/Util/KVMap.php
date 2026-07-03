<?php

namespace App\Util;

use App\Exception\BaseException as Exception;
use App\Exception\MapRecursionException;

class KVMap
{
    protected array $childs = array();

    protected function raise_not_exists(string $key): never
    {
        throw new Exception('The key "' . $key . '" is not in the map.');
    }

    protected function raise_exists(string $key): never
    {
        throw new Exception('The key "' . $key . '" is already in the map.');
    }

    protected function raise_get_not_map(string $key, string ...$path): never
    {
        throw new MapRecursionException(
            'Can not read sub-key "' . $next_key . '" of a non-Map key "' . implode('->', $path) . '".',
            ...$path
        );
    }

    protected function raise_set_not_map(string $key, string ...$path): never
    {
        throw new MapRecursionException(
            'Can not change sub-key "' . $next_key . '" of a non-Map key "' . implode('->', $path) . '".',
            ...$path
        );
    }

    public function add(string $key, mixed $value)
    {
        if (array_key_exists($key, $this->childs))
        {
            $this->raise_exists($key);
        }

        $this->childs[$key] = $value;
    }

    public function set_r(string $key, mixed $value): void
    {
        $this->childs[$key] = $value;
    }

    public function set(string $key, mixed $value): void
    {
        if (!array_key_exists($key, $this->childs))
        {
            $this->raise_not_exists($key);
        }

        $this->childs[$key] = $value;
    }

    public function get_r(string $key, mixed $default): mixed
    {
        if (!array_key_exists($key, $this->childs)) return $default;

        return $this->childs[$key];
    }

    public function get(string $key): mixed
    {
        if (!array_key_exists($key, $this->childs))
        {
            $this->raise_not_exists($key);
        }

        return $this->childs[$key];
    }

    public function develop_path(string ...$path): mixed
    {
        $result = null;

        if (count($path) < 1) $result = $this;
        else if (count($path) == 1)
        {
            $result = $this->get($path[0]);
        }
        else
        {
            $map = $this->get($path[0]);
            if (!is_a($map, KVMap::class))
            {
                $this->raise_get_not_map($path[1], $path[0]);
            }

            try
            {
                $result = $map->develop_path(...array_slice($path, 1));
            }
            catch (MapRecursionException $e)
            {
                $path = $e->get_path();
                $this->raise_get_not_map($path[-1], ...array_slice($path, 0, -1));
            }
        }

        if (!is_a($result, KVMap::class)) return $result;
        else return $result->as_array();
    }

    public function develop_path_r(mixed $default, string ...$path): mixed
    {
        try
        {
            return $this->develop_path(...$path);
        }
        catch (Exception $e)
        {
            return $default;
        }
    }

    protected function modify_path(bool $append, mixed $value, string ...$path): void
    {
        if (count($path) < 1) return;
        else if (count($path) == 1)
        {
            if ($append) $this->add($path[0], $value);
            else $this->set($path[0], $value);
        }
        else if (!array_key_exists($path[0], $this->childs))
        {
            $map = new KVMap();
            $map->modify_path($append, $value, ...array_slice($path, 1));
            if ($append) $this->add($path[0], $map);
            else $this->set($path[0], $map);
        }
        else
        {
            $map = $this->get($path[0]);
            if (is_a($map, KVMap::class))
            {
                try
                {
                    $map->modify_path($append, $value, ...array_slice($path, 1));
                }
                catch (MapRecursionException $e)
                {
                    $path = $e->get_path();
                    $this->raise_set_not_map($path[-1], ...array_slice($path, 0, -1));
                }
            }
            else
            {
                raise_set_not_map($path[1], $path[0]);
            }
        }
    }

    public function assign_path(mixed $value, string ...$path): void
    {
        $this->modify_path(true, $value, ...$path);
    }

    public function change_path(mixed $value, string ...$path): void
    {
        $this->modify_path(false, $value, ...$path);
    }

    public function contains(string ...$path): bool
    {
        if (count($path) < 1) return false;
        else if (!array_key_exists($path[0], $this->childs)) return false;
        else if (count($path) > 1)
        {
            $value = $this->get($path[0]);
            return (
                is_a($value, KVMap::class)
                ? $value->contains(...array_slice($path, 1))
                : true
            );
        }

        return true;
    }

    public function as_array(): array
    {
        $result = array();
        foreach ($this->childs as $key => $value)
        {
            $result[$key] = is_a($value, KVMap::class)
            ? $value->as_array()
            : $value;
        }

        return $result;
    }
}