# Plugins structure

* Status: accepted
* Deciders: Kevin TRAINI
* Date: 2024-11-04

## Context and Problem Statement

Currently, many plugins set their own namespace on multiple directories. For example plugin Tracker set namespace
`Tuleap\Tracker` on both `include` and `include/Tracker`. The same problem appears for tests directories.

This situation leads to some confusion and even duplications.

## Considered Options

* Use `include`
* Use `include/<plugin_name>`

## Decision Outcome

Chosen option: use `include`, because it avoids a repetition in the path (e.g `plugins/tracker/include/Tracker`).

`composer.json` of each plugin will now link plugin namespace `Tuleap\Name` to directory `include`. For tests, it will
link it to `tests/unit`, `tests/rest` and `tests/integration`.

```json
{
  "autoload": {
    "psr-4": {
      "Tuleap\\Template\\": ["include/"]
    }
  },
  "autoload-dev": {
    "psr-4": {
      "Tuleap\\Template\\": ["tests/unit", "tests/integration", "tests/rest"]
    }
  }
}
```

## Links

* [Plugins documentation](../doc/back-end/plugins.md)
