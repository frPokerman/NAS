# YAML syntax for plugin configuration

*[Home](../../README.md) > [Documentation](../INDEX.md) > [Reference](./INDEX.md) > YAML syntax for plugin configuration*

## YAML basics

The YAML parser used to read a plugin configuration walk through 3 layers of rules and syntax to know before writing your own configuration:

1. The universal YAML syntax (see below for documentation);
2. The Symfony's YAML component (because this parser doesn't implement some advanced YAML features, also check [the Symfony YAML component documentation page](https://symfony.com/doc/current/components/yaml.html) for both standard and php-specific features);
3. The YAML parser implemented by this project, detailed below.

## What are configurations

The Settings plugin read YAML configuration files to define the behaviors of a specific plugin or the whole server. To learn more about the configuration files' location, read the [walkthrough about configuring a plugin](../guides/configure-a-plugin.md).

A configuration is a key associated with a value. In the YAML context, every property whose value is not a mapping become a configuration. During runtime, plugins can submit queries to the Settings plugin to read or write the value of a configuration based on its key.

The Settings service provides an interface (@ `/settings[?p=<plugin id>]`) to simplify the access to these configurations using a browser. To further improve accessibility and understanding of what each configuration do, it is recommended to **document** your configuration files.

## Configuration documentation

All documentations for one property (*a.k.a.* configuration) is precised in an inline comment (starting with `#`) and the corresponding **documentation token** (preceded by `@`). Comments without any token or with unknown token are parsed as regular comments, and should not be exposed during runtime.

**All documentations are optional, and allowed in any order.** In the following, **Default** refers to the expected behavior if the token is not specified.

### Documentation token

`@name` - The text displayed to distinguish a configuration from another.

- **Syntax** : `@name <a less technical word>`
- **Default** : The name is set to the key separated in words where the key contains dashes (`-`). The first word is capitalized (the first letter is set uppercase).

`@type` - The data type of all allowed values. Mixed values are not supported: if necessary, consider using strings and deserialize them during runtime.

- **Syntax** : `@type <data type>`
- **Default** : The type is set to the data type of the parsed configuration value.

#### Allowed types

Most types can be determined automatically (*auto*) from the configuration value.

| Type     | Allowed values      | Comment                  |
| -------- | ------------------- | ------------------------ |
| string   | All textual values  | *auto*                   |
| integer  | Integer values      | *auto*                   |
| double   | Floating values     | *auto*                   |
| boolean  | True or False       | *auto*, case-insensitive |
| time     | Strings formatted `XX:YY` or `HH:XX:YY` where 0 <= `XX`, `YY` < 60 and 0 <= `HH` < 24 | Must be specified |
| select   | All values in the configuration **list** (*see the `@list` token*). | *auto*                   |

`@desc` - The text displayed below the name to precise the behavior of a configuration, its possible values or other usage notes.

- **Syntax** :
```yaml
# @desc <first line of description>[\
#       <an other line of description>]
```
*Indentation is optional.*
- **Default** : No description is displayed.

A backslash `\` can be added at the end of the line to include the next line too in the description. The backslash will be replaced by a line feed, meaning the two texts will be displayed on different lines. This is ignored if the next line starts with a valid documentation token.

If no backslash is found at the end of the line, the next line is parsed as a documentation if it starts with a valid documentation token, else as a regular comment.

`@docs` - A reference to a resource documenting this configuration or its possible values.

- **Syntax** : `@docs <URL of the documentation page>[ <text to display>]`
- **Default** : No documentation or help is provided.

A custom text can be specified instead of the default "*See more.*", following the URL and separated by a space.

`@list` - A list of possible values for this configuration. At any time this configuration's value must be in this list.

- **Syntax** : `@list <items, separated, by, commas>`
- **Default** : The configuration can take any value of its type, unless a **list** is created anyway using `@from`, the `select` type or an enum as the value.

If the provided dataset is an array, then the value is expected to be one of them.

> **Not yet implemented:**\
Else if the provided dataset is a mapping, the value of the property value is expected to be one of the key.

`@from` - (Similar to `@list`, but the list is retrieved dynamically by a function call) A function that returns a list of possible values for this configuration. At any time this configuration's value must be in this list.

- **Syntax** : `@from <service id>::<public method>`
- **Default** : The configuration can take any value of its type, unless a **list** is created anyway using `@list`, the `select` type or an enum as the value.

When parsing a configuration with this documentation, if a service has the tag `app.yaml_available` with the key that matches the `<service id>` parameter, then the method `<public method>` of this service is called, and the result become the list of possible values for this configuration.

It is expected from this method to return a mapping where values are more comprehensive than the keys. But if an array is returned, the keys become the indexes.

In the YAML configuration file or during runtime, the key is used to refer to the corresponding possible value, however the value is displayed on the interface.

## Parsing enums and constants

Instead of invoking a service using `@from`, a reference to an enum can be passed as the property value in plain YAML:

```yaml
<yaml-property>: !php/enum <Namespace\Separated\By\Backslashes\><EnumName>::<EnumConstant>
# will be parsed as an Enum object
```

If it is a backed enum, you may precise to use the value:

```yaml
language-full-name: !php/enum Example\Enum\FullNameOf::PHP->value
# will be parsed as the value of the enum
```

In the latter, the value is parsed as a string (or whatever type the enum is), but for configuration purposes, it is recommended to use an enum object (not its `->value`) so that the parser can make a list of possible values for the configuration by listing the constants in the same enum.

To read more about parsing PHP constants or enums from YAML, see the [Symfony YAML component documentation page](https://symfony.com/doc/current/components/yaml.html#parsing-php-enumerations).

## Examples

- **Basic YAML without documentation**

```yaml
max-client: 20
min-price: 0.3
contact: support@ourcompany.com
```

**Will output :**

### Max client ______________________ 20
### Min price _______________________ 0.3
### Contact _________________________ support\@ourcompany.com

___

- **Configuration name and description**

```yaml
# @desc The maximum amount of clients connected at the same time.
max-client: 20

# @name Minimum price
# @desc The minimum price of monthly subscriptions after all discounts have been applied.
min-price: 0.3

# @desc Also check our profile on our website:
# @docs https://ourcompany.com/about
contact: support@ourcompany.com
```

**Will output :**

### Max client ______________________ 20
The maximum amount of clients connected at the same time.

### Minimum price ___________________ 0.3
The minimum price of monthly subscriptions after all discounts have been applied.

### Contact _________________________ support\@ourcompany.com
Also check our profile on our website:\
*[See more](#https://ourcompany.com/about)*.

___

- **Configuration lists and multi-lines descriptions**

```yaml
# @name Color theme
# @desc The theme of the interface.
# @list blue, dark, light, orange, lime
theme: dark

# @name App Mode
# @desc The starting mode of the application.
mode: !php/enum App\Enum\AppMode::LAZY

# @name Language
# @desc The interface's language.\
#       Used for translations
# @from translate::available_languages
lang: nl
```

Assuming these PHP scripts exist:

```php
namespace App\Enum;

enum AppMode: string
{
    case LAZY = 'Lazy loading';
    case FAST = 'Fast loading';
    case VERBOSE = 'Verbose mode';
    case DEBUG = 'Debugging mode';
}
```

```php
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.yaml_available', [ 'key' => 'translate' ])]
class MyTranslationService
{
    public function available_languages(): array
    {
        return array(
            'de' => 'Deutsch',
            'en' => 'English',
            'es' => 'Español',
            'fr' => 'Français',
            'nl' => 'Nederlands'
        );
    }
}
```

**Will output :**

### Color theme _________________ dark
The theme of the interface.

| Possible values |
| :-------------: |
| blue            |
| **dark**        |
| light           |
| orange          |
| lime            |

### App Mode ____________________ Lazy loading
The starting mode of the application.

| Possible values  |
| :--------------: |
| **Lazy loading** |
| Fast loading     |
| Verbose mode     |
| Debugging mode   |

### Language _________________________ Nederlands
The interface's language.\
Used for translations

| Possible values  |
| :--------------: |
| Deutsch          |
| English          |
| Español          |
| Français         |
| **Nederlands**   |