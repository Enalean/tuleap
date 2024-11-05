# Independent libraries for shared frontend code

* Status: accepted
* Deciders: Thomas GERBET, Joris MASSON
* Date: 2022-04-08

Technical Story: [request #26381 Prevent relative imports outside of a package][0]

## Context and Problem Statement

Since we started using a module bundler (webpack), we have taken the habit of importing files from outside our context ("upper" in the path than our `package.json`) with deep relative paths (such as `../../../../src/tools/<file-name>`). It raises some problems though:
* It hides the dependencies. We have to search all source code to find usages of a given file.
* Some files should be internal and should never be imported directly.
* It causes issues with [caching][2] as those build tools work at the `package.json` level and don't expect such relative imports.
* It causes issues with dependency upgrades. For shared Vue components, unless all versions of Vue are bumped simultaneously (which can be difficult in case of big changes), the build system will fail with weird TypeScript errors.
* It causes type-checking issues. Pulling a file from upper in the path than the `tsconfig.json` file causes TypeScript to apply the wrong options to that file.

## Decision Outcome

A distinction is drawn between "applications" and "libraries" in Tuleap frontend. A "Library" is a bundle of shared code that is used by one or more "Libraries" or "Applications". An "Application" is not used by anyone, it is a leaf in the dependencies graph. Applications' purpose is to be loaded alongside Tuleap HTML and to act on it. Libraries can be Vue components, [Custom elements][3], TypeScript code, CSS themes, or even raw SCSS, or a combination of each.

Shared frontend code must be separated in independent libraries, each with a dedicated `package.json` and its associated `build`, `watch`, `test`, and [typecheck][1] tasks.

Global libraries must be in `lib/frontend/<library-name>/`. Plugin-scoped libraries must be in `plugins/<plugin-name>/scripts/lib/<library-name>/`.

Plugin libraries obey the same rules as plugins themselves: Core must never depend on any Plugin, Other plugins must only depend on Core or declared plugin dependencies (for example AgileDashboard -> Tracker). No cycles are allowed.

### Positive Consequences

* It makes it possible to add [caching][2] to the frontend build-system.
* It makes dependencies more visible since they are now at the `package.json` level. We no longer have to search all source code to find usages of a given file.
* Leveraging [Subpath exports][4], it is possible to forbid importing "internal" files. Users of libraries can be forced to only use public interfaces.
* It makes it possible to progressively switch the usage of one library in favor of another. Big monoliths such as the TLP library have a lot of usages and are much harder to replace. With small libraries, it is easier to replace usage in any given application by another library.

### Negative Consequences

* It's more work: we have to write a `package.json` for every shared piece of code, plus a build configuration, a test configuration, etc.
* It introduces more places where developers must run npm scripts. In a previous decision (before writing ADRs), we chose to organize Tuleap plugins and core so there would be only one place to run "npm scripts" to build everything in that context. Scripts in `src/` would build everything for Tuleap core, and scripts in `plugins/<plugin-name>/` for each plugin. This decision introduces several new places for libraries. When working on a library, developers will have to identify an application where it is used and run as many `watch` scripts as necessary to rebuild the library and its users up to the final application. However, running `make post-checkout` is much faster since [ADR-0008][2], which mitigates this negative consequence.

## Links

* [ADR-0008: Caching of the build results of the JS toolchain][2]

[0]: https://tuleap.net/plugins/tracker/?aid=26381
[1]: 0010-ts-typechecking-individual-task.md
[2]: 0008-cache-js-toolchain-build-results.md
[3]: https://developer.mozilla.org/en-US/docs/Web/Web_Components/Using_custom_elements
[4]: https://nodejs.org/api/packages.html#subpath-exports
