# NAS

A NAS server with additional plugins.

___

Hi, hi! Welcome on this ambitious project!

You may be lost but in case you're not, I am a "junior" developer (regarding PHP: not even born) and I am making here just a (not at all) simple NAS server (not even sure what that means).

If you're reading this, then you are ~~definitively lost~~ watching the very beginning of this wonderful project that (I hope) will be very useful for me, but who knows? maybe for you too.

___

The project idea is to have a **portable server** (and router) where you can store **musics, videos, documents *etc...*** and that you can **bring with you** wherever you go on whatever mean of transport (don't watch videos while driving though), that you can connect to with (quite) all your devices to **stream, share, work, log, or search in your docs without connecting to Internet**.

The goal? **Avoid steaming again and again** the same musics (or videos) from accross the world and beyond (satellites), **connect all your devices** together without handing your private life to Internet on a silver plate, or view the local map or record your journey in a deep forest, a desert, underwater or in the sky!

The cost? I don't know the minimum configuration yet, but it should be able to run on a 1GB (RAM) Raspberry. That is all you need to take the hand back on your devices.

**Expand your network into an ecosystem.**

## 🎯 Latest version

>   Version number: 26.1.7 \
    Date: 2026-07-03

Add the file list for configuration files and a custom recursive key-value map structure.

The server-wide and plugin configurations are located in the root-level `config/` directory (not to confused with `web-server/config/` for the Symfony's configuration). Each configuration file must be a YAML file.

To access a configuration `param` in the file `config/xyz/abc.yaml`, call `ConfigList::get` with `'xyz.abc:param'`.

**Syntax:**

```
path :  <file>
        <folder>.<file>
        <file>:<key>
        <folder>.<file>:<key>
```

To modify the setting's value, call `ConfigList::set` with the same config id (`'xyz.abc:param'`) followed by the new value. The setting **must exist** before changing its value: **it is not possible to add a new setting in the configuration file** using the `ConfigList::set` method.

**Usage:**

Because `ConfigList` is a Symfony service, it is initialized by the framework using [autowiring](https://symfony.com/doc/current/service_container/autowiring.html):

```php
use App\Service\FileList\ConfigList;

class ControllerExample
{
    // ...

    public function my_function(int $x, ConfigList $config): int
    {
        return $x + $config->get('xyz.abc:param');
    }
}

// ...

$ctr = new ControllerExample();
$y = $ctr->my_function(10);
// assuming 'param' is set to 3 in config/xyz/abc.yaml,
var_dump($y);   // outputs int(13)
```

**Plugins integration:**

Plugins' configuration files are located in the subdirectory `config/plugins/` and named like their plugin id or in another subdirectory `config/plugins/<plugin id>/`.

Plugins that inherits from `BasePlugin` (namespace `App\Plugin`) can access their parent `BasePlugin::config` method to get or set a configuration related to that plugin, but to do so, the child plugin **must pass the `ConfigList` to its parent** in the constructor:

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

### 📅 Planned releases

| Release | Features                    |
| ------- | --------------------------- |
| 26.2    | The Settings plugin         |
| 26.3    | Tests                       |
| 26.5    | Changelogs                  |
| 26.10   | Full documentation          |

___

## 🚩 Installation and configuration

### 🖥️ Hardware requirements

I don't know the minimum configuration yet, but I personally use a 1GB (RAM) Raspberry Pi 4 B for my own, so:

| Requirement | Minimum                | Recommanded           |
| ----------: | :--------------------: | :-------------------: |
| OS          | Anything that runs PHP | Raspberry Pi OS       |
| CPU         | ?                      | Quad core Cortex-A72  |
| RAM         | ?                      | 1 GB                  |
| Storage     | 4 GB                   | 64 GB (musics, videos)|
| Wi-Fi       | ?                      | 2.4 GHz / Ethernet    |

### 📦 Installation

*To be determined.*

## 🔌 Plugins showcase

Plugins are services that can be enabled (or disabled) depending on your needs and your resources. They add new powerful and tailored tools in addition to the main functionality of serving files.

![Plugins list preview](dev/design/T2Plugins.png)

> Notes: Plugins are presented here as illustration and are likely to change during development.

### 🔩 Core plugins

These plugins can not be disabled. At this point, just uninstall the program... The program doesn't do anything without any other plugin.

* **Shell** - The command line interface of the server.
* **Settings** - The configuration center of the core program and every other plugins.

### 🛞 Native plugins

These plugins are enabled by default as they may be useful for the majority of users, but can easily be disabled if needed.

* **Files** - A storage for your medias and documents to share accross devices or for your backups.
* **Interface** - A graphical user interface (for browsers).
* **Resources** - A monitoring center for resources usage and statistics.
* **Search** - A powerful search bar to find everything on your server.
* **Network** - A Wi-Fi network (proxy) you can connect to with your devices. Can be used as a DNS server, a network-wide advertising blocker, or your personal VPN.
* **Snippets** - "Everything can be simplified". Easy shortcuts to automate your daily tasks (may not do the chores).

### 🔗 Additional plugins

These plugins may not be suited for everyone's use but will surely find their fans.

* **Profiles** - Separates the files and behaviors depending on who is connected (accounts).
* **Messages** - A all-in-one inbox for all your messages.
* **Passwords** - A credential manager.
* **Projects** - Developer tools to host, deploy, test and backup all projects.

## 📚 Enabling / disabling a plugin

*To be determined.*

## 📖 Creating a plugin

As it stands, a plugin can be defined in 3 independent and optional ways:

### 1. The controller

Every PHP file in the directory `web-server/src/Plugin/` will be considered as a plugin by the `PluginList` service (namespace `App\Service\FileList`) and returned by the `PluginList::get_config_list` method and the `/api/plugins/list` endpoint.

At that moment, the endpoint is completely unused and so is the `PluginList::get_config_list` method except by the endpoint.

> **Imminent change:** The plugins returned by this method correspond to the lowercase filename without the extension and might differ from the actual plugin id, *e.g.* the Interface plugin whose controller is named "WebInterface" but its id is "interface".

Plugins can (and should) be used as [Symfony services](https://symfony.com/doc/current/service_container.html) so that you don't need to construct them yourself, instead the framework autowires them whenever you define them in a function's parameters.

Additionally, you can use a [`Route` attribute](https://symfony.com/doc/current/routing.html#creating-routes-as-attributes) on a plugin function to automatically call this function whenever the user tries to reach the specified endpoint.

Plugins that inherit from the `BasePlugin` class (namespace `App\Plugin`) can access the `BasePlugin::config` shorthand to read or write a plugin setting. See the [latest version summary](#-latest-version) for more details;

> Note that to inherit from `BasePlugin` a plugin **must pass the `ConfigList` service** to the constructor, even if this plugin doesn't access any configuration file.

### 2. The interface

Every folder in the directory `web-server/templates/` that contains a file named `main.html.twig` will be considered as a plugin by the `WebInterface` plugin.

Such template can be accessed at `/<folder name>`.

> Note that it is recommended to name the folder like the plugin id else the template could become unavailable in a future release.

### 3. The configuration

Every YAML file or subfolder in the directory `config/plugins/` will be considered as a plugin by the `ConfigList` service (namespace `App\Service\FileList`). Both a YAML file and a subfolder can exist with the same name simultaneously.

Even if the `BasePlugin::config` shorthand only gives access to the configuration file or subfolder matching the calling plugin id, any plugin or any controller can call the `ConfigList::get` method and access any configuration file or subfolder, so there is no restriction for a plugin to have its configuration at a specific location, as long as it is located within the root-level `config/` directory.

## 🤝 Contributing

[Creating a plugin](#-creating-a-plugin) is already a huge contribution, but if you are interested in taking an active part in the project, contact me first (see [credits](#-credits)) and I will be happy to give you all necessary access to the project.

If you want to report an issue or make a suggestion, you are more than welcome to use the GitHub Issues section of the project.

Finally, if you prefer suggesting your own idea of the project, feel free to fork the repository and edit my work as you wish.

## 🎓 Credits

I don't like credits.

Although and if you want, you can contact me at santfals@gmail.com.

By the way: I am a certified human being. As long as I will be the only person working on this project, I can guarantee that not a single line of code (and text) has been generated by an artificial intelligence without being read, tested and approved by me, the human.