<?php

namespace App\Data;

use App\Exception\BaseException as Exception;
use ErrorException;
use JsonSerializable;
use Psr\Container\ContainerInterface;
use UnitEnum;

class Configuration implements JsonSerializable
{
    const string YAML_COMMENT = '#';
    const string ESCAPE_NEW_LINE = '\\';
    const string LIST_SEPARATOR = ',';
    const string METHOD_SEPARATOR = '::';
    const string PARAM_TOKEN = '@';

    const string PARAM_NAME             = 'name';
    const string PARAM_TYPE             = 'type';
    const string PARAM_DESCRIPTION      = 'desc';
    const string PARAM_DOCUMENTATION    = 'docs';
    const string PARAM_LIST             = 'list';
    const string PARAM_GET_LIST_FROM    = 'from';

    protected string $type = '';
    protected string $name = '';
    protected string $description = '';
    protected string $documentation = '';
    protected string $doc_text = '';
    // If not empty, $value MUST match one of its value (not a key!)
    protected array $possible_values = array();
    protected array $comments = array();

    private ContainerInterface $container;

    public function __construct(
        protected mixed $key,
        protected mixed $value,
        array $meta,
        ContainerInterface $service_provider
    )
    {
        $waiting_new_line = false;
        foreach ($meta as $line)
        {
            $line = trim($line);
            if (!str_starts_with($line, self::YAML_COMMENT)) continue;

            $line = trim(substr($line, 1));
            if (!str_starts_with($line, self::PARAM_TOKEN))
            {
                if ($waiting_new_line)
                {
                    $waiting_new_line = str_ends_with($line, self::ESCAPE_NEW_LINE);
                    $this->description = substr($this->description, 0, -1)."\n".$line;
                }
                else
                    $this->comments[] = $line;

                continue;
            }

            $token = strtolower(substr($line, 1, strpos($line, ' ', 1) - 1));
            $content = trim(substr($line, strlen($token) + 1));
            switch ($token)
            {
                case self::PARAM_NAME:
                    $this->name = $content;
                    $waiting_new_line = false;
                    break;
                case self::PARAM_TYPE:
                    if (!$this->type && $this->type != $content) $this->type = $content;
                    else throw new Exception(
                        'Can not reassign data type of configuration "'.$this->key.'" of type "'.$this->type.'" to "'.$content.'".'
                    );
                    $waiting_new_line = false;
                    break;
                case self::PARAM_DESCRIPTION:
                    $this->description = $content;
                    if (str_ends_with($line, self::ESCAPE_NEW_LINE)) $waiting_new_line = true;
                    break;
                case self::PARAM_DOCUMENTATION:
                    [ $url, $text ] = explode(' ', $content, 2) + array('', '');

                    $this->documentation = $url;
                    $this->doc_text = $text;

                    $waiting_new_line = false;
                    break;
                case self::PARAM_LIST:
                    $list = array_map(function($item)
                    {
                        return trim($item);
                    }, explode(self::LIST_SEPARATOR, $content));
                    
                    $this->type = 'select';
                    $this->possible_values = $list;

                    $waiting_new_line = false;
                    break;
                case self::PARAM_GET_LIST_FROM:
                    try
                    {
                        [ $service_id, $method ] = explode(self::METHOD_SEPARATOR, $content, 2);
                    }
                    catch (ErrorException $th)
                    {
                        // If you are trying to refer to an enum, please use the !php/enum operator.
                        //     A whole walkthrough is available in the documentation.
                        throw new Exception(
                            'No method specified or wrong separator in the documentation token @from for configuration "'.$this->key.'".'
                        );
                    }

                    // TODO: check if $s exists
                    $s = $service_provider->get($service_id);
                    $list = $s->{$method}();
                    
                    $this->type = 'select';
                    $this->possible_values = $list;
                    $this->value = $list[$this->value];

                    $waiting_new_line = false;
                    break;
                default:
                    if ($waiting_new_line)
                    {
                        $waiting_new_line = str_ends_with($line, self::ESCAPE_NEW_LINE);
                        $this->description = substr($this->description, 0, -1)."\n".$line;
                    }
                    else
                        $this->comments[] = $line;

                    break;
            }
        }

        if (!$this->name) $this->name = ucfirst(str_replace('-', ' ', $key));

        if ($this->value instanceof UnitEnum)
        {
            // TODO: what if not a backed enum? (should be not an instance of BackedEnum)
            $this->type = 'select';
            $this->value = $value->value;
            $this->possible_values = array_combine(
                array_column($value::cases(), 'name'),
                array_column($value::cases(), 'value')
            );
        }
        else if (!$this->type) $this->type = gettype($this->value);

        if (!$this->verify($this->value))
        {
            throw new Exception(
                'Configuration "'.$this->key.'" of type "'.$this->type.'" can not be parsed or contains syntax errors.'
            );
        }
    }

    protected function autowire(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    protected function verify(mixed &$value): bool
    {
        switch ($this->type)
        {
            case 'time':
                if (gettype($value) != 'string') return false;
                $div = explode(':', $value);
                
                // Check that each division of time is between 0 and 59 and contains at most two digits
                foreach ($div as $frac)
                {
                    $n = (int) $frac;
                    if (strlen($frac) > 2 || $n < 0 || $n >= 60) return false;
                }
                // Check that hour is no more than 23
                if (((int) $div[0]) >= 24)
                {
                    if (count($div) >= 3) return false;
                    
                    // Or add a new division for hours if not present
                    // Because HTML "time" inputs will parse 00:30:00 but not 30:00
                    $value = '00:'.$value;
                    $div = explode(':', $value);
                }
                
                // Check that there are at least two divisions, where each contains at least two digits
                if (count($div) < 2) $value = '00:'.str_pad($value, 2, '0', STR_PAD_LEFT);
                else if (count($div) < 4) $value = implode(':', array_map(function($frac)
                {
                    return str_pad($frac, 2, '0', STR_PAD_LEFT);
                }, $div));
                else return false;

                return true;
            case 'string':
            case 'integer':
            case 'double':
            case 'boolean':
            case 'array':
                return gettype($value) == $this->type;
            case 'select':
                return in_array($value, $this->possible_values);
            default:
                return false;
        }
    }

    public function getkey(): string
    {
        return $this->key;
    }

    public function getvalue(): mixed
    {
        return $this->value;
    }

    public function gettype(): string
    {
        return $this->type;
    }

    public function getname(): string
    {
        return $this->name;
    }

    public function getdescription(): string
    {
        return $this->description;
    }
    public function hasdescription(): bool
    {
        return $this->description != '';
    }

    public function getdocumentation(): string
    {
        return $this->documentation;
    }
    public function hasdocumentation(): bool
    {
        return $this->documentation != '';
    }

    public function getdoc_text(): string
    {
        return $this->doc_text;
    }
    public function hasdoc_text(): bool
    {
        return $this->doc_text != '';
    }

    public function getpossible_values(): array
    {
        return $this->possible_values;
    }

    public function jsonSerialize(): mixed
    {
        return $this->value;
    }
}