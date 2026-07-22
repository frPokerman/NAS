# Walktrough: Configure a Plugin

*[Home](../../README.md) > [Documentation](../INDEX.md) > [Walkthroughs](./INDEX.md) > Configure a Plugin*

## Creating a configuration

The server-wide and plugin configurations are located in the root-level `config/` directory (not to be confused with `web-server/config/` for the Symfony's configuration). Each configuration file must be a YAML file.

Plugin configuration files are, by default, located in the subdirectory `config/plugins/` and named like their plugin id or in another subdirectory `config/plugins/<plugin id>/`.

## Accessing a configuration

Currently, two classes can directly access a configuration. The Settings plugin can access all configurations inside the config folder and is available everywhere, while the `BasePlugin` provides a shorthand method that makes access to the configuration of a plugin easier, but is only available in a Plugin that inherits from this base.

### Using the Settings plugin

The Settings plugin provides three public method to access a configuration:

- `get` - to read the value of one configuration from any file, or groups of configuration that can be all configurations in a file, all configurations in a YAML mapping, or all configurations of all files in a configuration sub-folder.
- `set` - to write the value of one configuration.
- `get_plugin_config` - is a shorthand to read all configurations listed in a plugin file, also available in a Twig template as `plugin_config("<plugin id>")`.

#### Syntax

```
path: [... <folders>.]<file>[:[... <mappings>.]<key>]
      [... <folders>.]<subfolder>
```

#### Values

* `<folders>` - *(Optional)* list of folders separated by periods;
* `<file>` - YAML configuration file to read;
* `<subfolder>` - folder containing the configuration files to be read;
* `<mappings>` - *(Optional)* list of period-separated keys corresponding to mappings (*a.k.a.* YAML objects);
* `<key>` - *(Optional)* key of the configuration to read.

#### Examples

```
xyz.abc:param
// Access property "param" in the file "config/xyz/abc.yaml"

xyz
// Get all configurations in "config/xyz.yaml" and in "config/xyz/"

xyz:alpha
// Access property "alpha" in the file "config/xyz.yaml"

xyz:omega.sigma

// Access child property "sigma" of the object "omega" in the file "config/xyz.yaml"
```

#### Usage

Because plugins are Symfony services, it is recommended to initialize the Settings plugin through [autowiring](https://symfony.com/doc/current/service_container/autowiring.html).

```php
use App\Plugin\Settings;

class ControllerExample
{
    // ...

                                        // Triggers autowiring
    public function my_function(int $x, Settings $settings): int
    {
        return $x + $settings->get('xyz.abc:param');
    }
}

// ...

$ctr = new ControllerExample();
$y = $ctr->my_function(10); // Notice how no second parameter is specified, whence the "auto"-wiring
// assuming 'param' is set to 3 in config/xyz/abc.yaml,
var_dump($y);   // outputs int(13)
```

### Using the `BasePlugin` shorthand

The `BasePlugin` class only provides one unique method to read and write a configuration at the same time: `BasePlugin::config`.

#### Syntax

The syntax of this function is the same as for the [previous methods](#using-the-settings-plugin), except that the function automatically precises the config path corresponding to the calling plugin. All you need to give to the function is the `key` (name) of the configuration as it is written in the YAML file and optionally its parent mappings' key or parent folders:

```php
$this->config("abc:param");
// Will read the property "param" in "config/plugins/<plugin id>/abc.yaml".

$this->config("alpha", "foo");
// Will set the property "alpha" in "config/plugins/<plugin id>.yaml" to "foo".

$this->config("omega.sigma", "bar");
// Will set the property "sigma" of object "omega" in "config/plugins/<plugin id>.yaml" to "bar".
```

Optionally, you can provide a value to assign to this configuration.

> Note: This shorthand read configuration files in the subdirectory `config/plugins/` and can only access the files inside whose name match the id of the calling plugin, or files in a subdirectory whose name match this id. To access other configurations, use the Settings plugin.

#### Usage

All plugins that inherit from `BasePlugin` can access the `BasePlugin::config` method when they have the **`Plugin` attribute** with their plugin id as parameter:

```php
<?php
// web-server/src/Plugin/PluginTest.php

namespace App\Plugin;

use App\Attribute\Plugin;
use App\Plugin\BasePlugin;

// Define this class as a plugin and precise its id
#[Plugin('test')]
class PluginTest extends BasePlugin
{
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

**Important:** the parent constructor uses **autowiring** to access the Settings service. This means that child classes should not override the `__construct` method. Instead, they can override the `construct` method.

If you need to override the constructor (*e.g.* to use autowiring with other services), you will need to call the parent constructor with the Settings service as parameter:

```php
use App\Attribute\Plugin;
use App\Plugin\BasePlugin;
// Import the Settings plugin
use App\Plugin\Settings;
// Import an other service
use MyService;

#[Plugin('my-plugin')]
class MyPlugin extends BasePlugin
{
    public function __construct(
        // Use autowiring in your constructor
        MyService $service,
        Settings $settings
    )
    {
        // Call the parent constructor with the Settings plugin
        parent::__construct($settings);
    }
}
```