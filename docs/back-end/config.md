# Configuration

When code requires configuration variables to be set by a site
administrator, they must be expressed as constant of classes with
attributes.

For backward compatibility, you might find existing reference to
variables by their literals (eg `ForgeConfig::get('sys_some_key')`) but
this form is deprecated.

## Declaring a new variable

A variable should be a constant in a class. The bare minimal is to
annotate the constant with `Tuleap\Config\ConfigKey` attribute.

``` php
final class MyClass
{
      #[Tuleap\Config\ConfigKey('This is a summary of my key')]
      public const MY_KEY = 'some_key';

}
```

You should also register the class that exposes the configuration key so
Tuleap knows were to look at them:

-   If `MyClass` belongs to Core, it should be added to
    `Tuleap\Config\GetConfigKeys::CORE_CLASSES_WITH_CONFIG_KEYS` array.
-   If `MyClass` belongs to a plugin, the plugin class must implement
    `Tuleap\Config\PluginWithConfigKeys` interface.

By implementing `Tuleap\Config\PluginWithConfigKeys` Tuleap will
automatically listen to the corresponding event, no need to do it
explicitly.

## Improve variable with attributes

There are a bunch of attributes that might be helpful (everything in
`Tuleap\Config` namespace):

-   `ConfigKeyHelp`: when summary in `ConfigKey` is not enough, you can
    put a long text here (`<<<HEREDOC` format recommended).
-   `ConfigKeySecret`: declare a variable that will store a secret (like
    a password). The secret will be automatically encrypted when saved
    in the DB.
-   `FeatureFlagConfigKey`: to be used (in place of `ConfigKey`) when
    introducing a new feature not ready for prime time. Needed to keep
    track of all feature flags across the DB.
-   Several attributes implements `ConfigKeyType`, they are convenient
    to declare the type of the variable as well as default value.
    Example: `#[ConfigKeyInt(50)]` => variable is an int and it\'s
    default value is `50`.

When several configuration variables belongs to the same \"category\" (a
plugin, \"database\", etc) it\'s good idea to add on associated class
the `#[ConfigKeyCategory]` attribute.
