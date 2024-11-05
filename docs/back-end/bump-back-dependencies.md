# Bump dependencies

At the beginning of each sprint, feature team has responsibility to
update dependencies of plugin they are working on.

## How to detect outdated dependencies

You can run `composer outdated`, you will have a list of dependencies to
bump.

## How to do the bump

Run the following command:

``` bash
composer update --with-all-dependencies
```
