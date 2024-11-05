# Bump dependencies

At the beginning of each sprint, feature team has responsibility to
update dependencies of plugin they are working on.

Some plugins have lot of legacy and some bumps will be painful, the main
aim of it is to work with a best effort cost:

-   we try to apply bump
-   if it fails we will pursue the bump only if it\'s easy, if not the
    bump must be discussed with the rest of team, and eventually added
    in backlog to plan it

## How to detect outdated dependencies in a plugin?

You can run `pnpm outdated` inside the plugin, you will have
a list of dependencies to bump. Example:

``` bash
$ pnpm outdated
Package                  Current  Wanted  Latest  Location
@juggle/resize-observer    3.3.0   3.3.1   3.3.1  @tuleap/plugin-roadmap
```

When we bump a dependency, we bump it everywhere in tuleap. For instance
the `@juggle/resize-observer` is used in
`roadmap` plugin but it is also used in `src`
and `list-picker`. The bump should concern the three
`package.json`

## How to do the bump

To be sure to bump the dependency everywhere it\'s used in tuleap, you
can run the following commands at the source of code:

``` bash
pnpm --recursive update @juggle/resize-observer
```

## How to detect outdated dependencies in all plugins?

If you want to check the dependencies status of the whole platform you
can run the following command:

``` bash
pnpm --recursive outdated
```
