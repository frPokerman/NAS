<?php

namespace App\Service;

use App\Data\Configuration;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class YAMLParser
{
    protected Filesystem $filesystem;

    public function __construct(
        #[AutowireLocator('app.yaml_available', indexAttribute: 'key')]
        private ContainerInterface $service_provider
    )
    {
        $this->filesystem = new Filesystem();
    }

    protected function loop_mapping(array &$mapping, string &$text): void
    {
        foreach ($mapping as $key => $value)
        {
            $pos = strpos($text, $key.':') - 1;
            $head = substr($text, 0, $pos);
            $meta = explode("\n", $head);

            $text = substr($text, strpos($text, "\n", $pos + 1) + 1);

            if (is_array($value))
            {
                $this->loop_mapping($value, $text);
            }

            $mapping[$key] = new Configuration($key, $value, $meta, $this->service_provider);
        }

        unset($key, $value);
    }

    public function read(string $filename, bool $parse_constants): array
    {
        $document = Yaml::parseFile($filename, $parse_constants ? Yaml::PARSE_CONSTANT : 0);

        $text = $this->filesystem->readFile($filename);
        // catch (IOExceptionInterface $exception)

        $this->loop_mapping($document, $text);
        return $document;
    }
}