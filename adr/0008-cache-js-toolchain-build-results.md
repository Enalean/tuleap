# Caching of the build results of the JS toolchain

* Status: ongoing experimentation, might be reverted or a completely different approach might be used in the end
* Deciders/Lead experimenter: Thomas Gerbet
* Date: 2021-12-31

## Context and Problem Statement

Tuleap is [(mostly) a monorepo](./0007-js-package-manager.md) and building it consume a non-negligible amount of time
and resources. This is an annoyance for developers because each time they pull sources they might need to rebuild the
integrality of Tuleap even if only a small part has changed.

As Tuleap grows bigger and new tools are introduced (e.g. converting some existing code to TypeScript to typecheck it)
the issue becomes more visible. Having the possibility to do incremental builds would lead to a better developer
experience.

## Considered Options

* Use [Nx](https://nx.dev/)
* Use [Turborepo](https://turborepo.org/)
* Use [Bazel](https://bazel.build/)

## Decision Outcome

No decision at this point. Currently experimenting with Turborepo.

## Pros and Cons of the Options

### Use [Nx](https://nx.dev/)

* Good, because it is a well established player in this space (we are less likely to hit bugs when using base features)
* Bad, because it does not seem easy to introduce progressively
* Bad, because it has a steep learning curve

### Use [Turborepo](https://turborepo.org/)

* Good, because setup is easy
* Good, because we can progressively introduce it
* Bad, because it is a quite recent project so we might encounter some infancy issues along the way

### Use [Bazel](https://bazel.build/)

* Bad, it has a very steep learning curve
