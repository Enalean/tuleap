# CLI tool

New CLI tool shoud depends on `Tuleap CLI (src/utils/tuleap.php)`

New CLI command should rely on the `tuleap` utility
(`src/utils/tuleap.php`) instead of defining a new entrypoint.

## How to add a new command

Every new CLI command should be added in the Tuleap CLI. To access to
Tuleap CLI, just run `tuleap` in your platform.

``` php
$application->add(
    new MyNewCommandClass
);
```

Tuleap CLI is based on [Symfony Command
class](https://symfony.com/doc/3.4/console.html/) and every new command
should follow this pattern.
