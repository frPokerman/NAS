# Walktrough: Configure a Plugin

*[Home](../../README.md) > [Documentation](../INDEX.md) > [Walkthroughs](./INDEX.md) > Configure a Plugin*

## Creating a configuration

The server-wide and plugin configurations are located in the root-level `config/` directory (not to be confused with `web-server/config/` for the Symfony's configuration). Each configuration file must be a YAML file.

Plugin configuration files are, by default, located in the subdirectory `config/plugins/` and named like their plugin id or in another subdirectory `config/plugins/<plugin id>/`.

## Accessing a configuration

Currently, two classes can directly access a configuration. The `ConfigList` service can access all configurations inside the config folder and is available everywhere, while the `BasePlugin` provides a shorthand method that makes access to the configuration of a plugin easier, but is only available in a Plugin that inherits from this base.

### Using the `ConfigList`

The `ConfigList` service provide three public method to access a configuration:

- `get` - to read the value of one configuration from any file, or groups of configuration that can be all configurations in a file, all configurations in a YAML mapping, or all configurations of all files in a configuration sub-folder.
- `set` - to write the value of one configuration.
- `get_config` - is a shorthand to read all configurations listed in a plugin file, also available in a Twig template.

**Syntax**

The `$config_path` argument of these functions must match this syntax:

```
path :  <file>
        <folder>.<file>
        <file>:<key>
        <folder>.<file>:<key>
```

Example: To access a configuration `param` in the file `config/xyz/abc.yaml`, call `ConfigList::get` with `'xyz.abc:param'`.

**Usage**

Because `ConfigList` is a Symfony service, it is recommended to initialize the service through [autowiring](https://symfony.com/doc/current/service_container/autowiring.html).

```php
use App\Service\FileList\ConfigList;

class ControllerExample
{
    // ...

                                        // Triggers autowiring
    public function my_function(int $x, ConfigList $config): int
    {
        return $x + $config->get('xyz.abc:param');
    }
}

// ...

$ctr = new ControllerExample();
$y = $ctr->my_function(10); // Notice how no second parameter is specified, whence the "auto"-wiring
// assuming 'param' is set to 3 in config/xyz/abc.yaml,
var_dump($y);   // outputs int(13)
```

### Using the `BasePlugin` shorthand

The `BasePlugin` class only provide one unique method to read and write a configuration at the same time: `BasePlugin::config`.

**Syntax**

The syntax of this function is the same as for the `ConfigList` methods, except that the function automatically precise the config path towards the configuration file of the calling plugin. All you need to give to the function is the `key` (name) of the configuration as it is written in the YAML file:


```php
BasePlugin::config("<key>");
BasePlugin::config("<mapping>.<key>");
BasePlugin::config("<key>", "new value");
BasePlugin::config("<mapping>.<key>", "new value");
```

Optionally, you can provide a value to assign to this configuration.

> Note: This shorthand read configuration files in the subdirectory `config/plugins/` and can only access the files inside whose name match the id of the calling plugin, or files in a subdirectory whose name match this id. To access other configurations, use the `ConfigList` service.

**Usage**

All plugins that inherit from `BasePlugin` can access the `BasePlugin::config` method. To do so, the child plugin **must pass the `ConfigList` to its parent** in the constructor:

```php
<?php
// web-server/src/Plugin/PluginTest.php

namespace App\Plugin;

use App\Plugin\BasePlugin;
use App\Service\FileList\ConfigList;

class PluginTest extends BasePlugin
{
    // Autowire the config list in the constructor.
    public function __construct(ConfigList $config)
    {
        // Pass the config list alongside the plugin id.
        parent::__construct('test', $config);
    }

    public function calculate(float $arg): string
    {
        // Fetch the value of a setting called 'factor'
        //   in config/plugins/test.yaml
        $value = $this->config('factor');
        return $arg . ' x ' . $value . ' = ' . ($arg * $value);
    }

    public function increment(): void
    {
        // Increment the value of 'factor' by 1
        $this->config('factor', $this->config('factor') + 1);
    }
}
```

Here it is assumed that this plugin will be automatically created by Symfony as a service when `use`d and autowired, but if for some reason you want to construct it yourself, you will need to pass a `ConfigList` as parameter to the constructor.