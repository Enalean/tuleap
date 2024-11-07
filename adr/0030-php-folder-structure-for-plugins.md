# PHP folder structure for plugins

* Status: accepted
* Deciders: Kevin TRAINI
* Date: 2024-11-04

## Context and Problem Statement

Currently, many plugins set their own PHP namespace on multiple directories. For example plugin Tracker sets its namespace
`Tuleap\Tracker` on both `include/` and `include/Tracker/`. The same problem appears for tests directories.

This situation leads to some confusion and even duplications.

## Considered Options

* Use `include/`
* Use `include/<plugin_name>/`

## Decision Outcome

Chosen option: use `include/`, because it avoids a repetition in the path (for example `plugins/tracker/include/Tracker/`).

`composer.json` of each plugin will now link plugin namespace `Tuleap\PluginName` to the `include/` directory. For tests, it will
link it to `tests/unit`, `tests/rest` and `tests/integration`.

```json
{
  "autoload": {
    "psr-4": {
      "Tuleap\\PluginName\\": ["include/"]
    }
  },
  "autoload-dev": {
    "psr-4": {
      "Tuleap\\PluginName\\": ["tests/unit/", "tests/integration/", "tests/rest/"]
    }
  }
}
```

## Links

* [Plugins documentation](../doc/back-end/plugins.md)
