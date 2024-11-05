# Autoload and Composer

Composer is used to manage the generation of the autoloaders. For a new
plugin, your `composer.json` file should look like this:

``` json
{
  "name": "tuleap/plugin-myplugin",
  "autoload": {
    "psr-4": {
      "Tuleap\\MyPluginNamespace\\": "include/"
    }
  }
}
```
