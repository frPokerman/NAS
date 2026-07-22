<?php

namespace App\Service\FileList;

use App\Exception\ResourceNotFound;
use App\Service\FileList\BaseList;

class ConfigList extends BaseList
{
    const string SEPARATOR = '.';
    const string EXTENSION = '.yaml';

    public function __construct()
    {
        parent::__construct('/../../config/');
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

    public function get_list(): array
    {
        return parent::get_files();
    }

    public function list_files_for(string ...$config_path): array
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
    
    public function resolve_file(string ...$file_path): string
    {
        return realpath($this->root . implode('/', $file_path) . self::EXTENSION);
    }
}