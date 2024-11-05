# Multiple independent apps per context

* Status: accepted
* Deciders: Thomas GERBET, Joris MASSON
* Date: 2022-10-20

Technical Story: [request #29233 Allow multiple frontend apps per context][0]

## Context and Problem Statement

In a previous decision (before writing ADRs), we chose to organize Tuleap plugins and core (which will hence be referred to as "contexts") so that there would be only one place to run "npm scripts" to build everything. The goal was explicitly to limit the number of places where "npm scripts" could be run in order to ease new developers' ramp-up time. Context "root" (`src/` folder for the core, plugin `plugins/<plugin-name>/` for each plugin) were the only places where we should run `build`, `watch` and `test` tasks. We invested a lot of energy into harmonizing every plugin to that setup. Recently we have encountered difficulties that call this choice into question:

* Many large contexts need more than one "application". When that was the case, we simply added entry points to our bundler and merged the dependencies.
* Merged dependencies make it much more difficult to do big migrations (for example from Vue 2 to Vue 3). One solution is to split each application's dependencies so that each has its own `package.json`. This has been done for Agile Dashboard for example. Otherwise, we must migrate all applications at once, but it's very hard.
* Sometimes it is necessary to also have a distinct `tsconfig.json` for each application to prevent weird TypeScript errors caused by mixing up two incompatible dependencies (for example mixing Vue 2 with Vue 3).
* Even when almost everything is separated, but the generated assets still go to a common folder, we are forced to be very careful and avoid overwriting the generated assets of other applications. It also causes [caching][2] issues, as the build tools work at the `package.json` level and don't go looking upper in the path. They don't know that the application writes to a folder above (`../../frontend-assets/`) and cannot apply caching correctly.
* It forces developers to build more than what is needed. In large contexts (for example Tuleap core), it degrades developer experience because rebuilding everything can take a while even in watch mode.

## Decision Outcome

Tuleap core and plugins contexts can each have several "Applications" (as defined by [ADR-0016][1]). Each application is completely independent of others, it has its own dependencies and build/test scripts, just like Libraries of [ADR-0016][1]. Each application builds its own `frontend-assets/` folder, separate from the others.

Core applications must be in `src/scripts/<application-name>/`. Plugin applications must be in `plugins/<plugin-name>/scripts/<application-name>/`.

### Positive Consequences

* Dependencies of each application are managed in a saner, independent way.
* It's easier to progressively upgrade dependencies of each application separately (especially big ones such as Vue).
* [Caching][2] the frontend build-system is more reliable.
* It's possible to rebuild more fine-grained parts of Tuleap frontend.

### Negative Consequences

* It's more work: we have to write a `package.json` for each application, plus a build configuration, a test configuration, etc.
* It introduces more places where developers must run npm scripts. However, running `make post-checkout` is much faster since [ADR-0008][2], which mitigates this negative consequence.
* Building the Tuleap RPMs is more complicated. Scripts folders must be inspected to keep frontend-assets folders.
* The Nginx rule to serve frontend assets is more complicated.

## Links

* [ADR-0016: Independent libraries for shared frontend code][1]
* [ADR-0008: Caching of the build results of the JS toolchain][2]

[0]: https://tuleap.net/plugins/tracker/?aid=29233
[1]: 0016-frontend-libraries.md
[2]: 0008-cache-js-toolchain-build-results.md
