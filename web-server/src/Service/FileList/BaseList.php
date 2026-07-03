<?php

namespace App\Service\FileList;

use App\Exception\ResourceNotFound;
use DirectoryIterator;

abstract class BaseList
{
    protected string $root;
    protected $files = array();

    protected function __construct(string $relative_path, bool $recursive = true)
    {
        $this->root = realpath($_SERVER['DOCUMENT_ROOT'] . $relative_path) . '/';
        $this->files = $this->list_all($recursive);
    }

    protected abstract function match(string $filename, string ...$parent): bool;

    protected function transform(string $filename, string ...$parent): string
    {
        return explode('.', $filename)[0];
    }

    protected function join(string $filename, string ...$parent): string
    {
        $parent[] = $filename;
        return implode('/', $parent);
    }

    protected function on_directory_not_found(string $directory): string
    {
        throw new ResourceNotFound('folder', $directory);
    }

    protected function list_all(bool $recursive = true, string ...$parent): array
    {
        $directory = implode('/', $parent);
        $path = realpath($this->root . $directory);
        if (!$path)
        {
            $path = $this->on_directory_not_found($directory);
        }

        $list = array();

        $iterator = new DirectoryIterator($path);
        foreach ($iterator as $info)
        {
            if ($info->isDot()) continue;

            $name = $info->getFilename();
            if ($recursive && $iterator->isDir())
            {
                foreach ($this->list_all($recursive, ...array_merge($parent, array($name))) as $child)
                {
                    $list[] = $this->join($child, $name);
                }
            }
            else if ($this->match($name, ...$parent))
            {
                $list[] = $this->transform($name, ...$parent);
            }
        }

        return $list;
    }

    protected function get_files(): array
    {
        return $this->files;
    }

    protected function contains(string $filename): bool
    {
        return in_array($filename, $this->files);
    }
}