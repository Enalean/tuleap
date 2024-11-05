# Repository organization of the Tuleap codebase

* Status: accepted
* Deciders: Manuel VACELET, Thomas GERBET, Nicolas TERRAY, Joris MASSON
* Date: 2023-02-01

Technical Story: [request #30743: Merge external plugin repositories into the main repository](https://tuleap.net/plugins/tracker/?aid=30743)

## Context and Problem Statement

Tuleap codebase is mostly located inside a monorepo including everything except some plugins that are
maintained in their own repositories.

This unusual organization is a source of multiple challenges:
* It impacts the dev team velocity when internal API changes need to be propagated to those out-of-tree plugins.
  Propagation of those changes to out-of-tree plugins is also regularly "forgotten" which can lead to crashes in
  production environment.
* Our unique architecture (neither decoupled nor monorepo) is not supported by any tool, and we must do clever tricks
that break at each upgrade.
* The build process cannot use modern features, and it wastes a lot of resources at each developer action and each part
  of the build process.

## Considered Options

* Merge the external repositories into the monorepo
* Do nothing, keep the "split monorepo"

## Decision Outcome

Merge the external repositories into the monorepo. It appears to be the preferable solution for the long term
maintenance as it reduces the complexity and technical effort.

### Positive Consequences

* It makes the whole development environment much more simple. The current status of the build system is a very high
  level of complexity with barely one or two people able to understand & fix potential issues. It is a major threat
  for the sustainability of the project.
* It forces external contributors to make clear design decisions: what they contribute **must** be part of the monorepo
  (with all the consequences in terms of IP & maintenance). So it is very likely that local shenanigans will be
  implemented separately with APIs and generic needs will benefit to the whole code base.
* It forces to invest on extension mechanism less coupled to Tuleap Core (REST API, OAuth2, WebComponent, WASM, etc).
* There is only one repository to manage with only one set of CI pipelines.
* It removes technical challenges we encounter with our dev tools (e.g. everything can be integrated into the Psalm
  baseline without a hack, a shared lockfile can be used for JS dependencies…).
* Refactoring of the internal APIs cannot forget a plugin, everything being at the same place.

### Negative Consequences

* Having Tuleap Plugin (PHP code) outside of Tuleap Main Tree will no longer be possible with no way back.
* There is no longer an example of an out-of-tree plugin. Developers wanting to have one will need to figure out
  everything by themselves. This consequence must be put into perspective, Tuleap does not offer any kind of stability
  of its internal API so developer of out-of-tree plugin must already be aware of how it works and are forced to follow
  changes made in the main Tuleap repository to adapt their code.
