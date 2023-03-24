# JS package manager

* Status: accepted
* Deciders: Thomas GERBET, Joris MASSON
* Date: 2021-09-29

Technical Story: [request #23396 Use pnpm to replace npm and Lerna](https://tuleap.net/plugins/tracker/?aid=23396)

## Context and Problem Statement

Tuleap currently uses [npm 7](https://www.npmjs.com/) and [Lerna](https://lerna.js.org/) to
install/update/delete dependencies of our packages and to build the JS-related code to something
that can be consumed by end-users.

Tuleap is mostly organized with a mono-repo structure:
* most of the code is located in one single repository containing the majority of the build instructions
* the internal JS packages are divided into 3 categories:
  - the "libs" which are destined to be shared between multiple packages, they exist as a way to share code
  - the "apps" which are used to build front-end interfaces
  - some edge case situations where we use JS code to generate some backend code
* the internal JS packages have dependencies between them (and of course also to JS packages that are not internal)

However, some Tuleap plugins are stored and managed in external repositories. Those plugins are expected to be put in
the usual [plugins/](../plugins/) directory but cannot be expected to always be present. In this *split monorepo*
situation nothing in the main repository can be dependent on code or data located in one of those external repositories.

This structure presents challenges which forced us to adopt various, not pretty, hacks. The JS ecosystem has moved a
lot since the last time we took a serious look, it might be time to revisit it.

## Considered Options

* Keep using [npm 7](https://www.npmjs.com/)
* Migrate to [Yarn Classic](https://classic.yarnpkg.com/lang/en/)
* Migrate to [Yarn 2+ / Berry](https://yarnpkg.com/)
* Migrate to [pnpm](https://pnpm.io/)

## Decision Outcome

Chosen option: migrate to [pnpm](https://pnpm.io/) because it comes out the best solution at the moment (see below).

The decision might and should be revisited in the future to see if it is still the best available option.

## Pros and Cons of the Options

### Keep using [npm 7](https://www.npmjs.com/)

* Good, because it is the most common package manager in the JS community
* Good, because it is already what we are using so nothing needs to be done
* Bad, because we cannot use the workspace feature due to our "split monorepo":
  * we need to manually clean our internal dependencies from the lockfiles
  * we need to use [Lerna](https://lerna.js.org/) to build the packages in topological order which seems overkill since
we do not use or need any of the main features of the tools
* Bad, because we are currently encountering a bug in the way one of the low-level part of npm works, which force us to
[workaround it](https://tuleap.net/plugins/git/tuleap/tuleap/stable?a=commit&h=839ff202fd304cbb639b76dc8ae04ac786e5195e)

### Migrate to [Yarn Classic](https://classic.yarnpkg.com/lang/en/)

* Good, because it is one of the major JS package managers
* Bad, because it does not come with a workspace feature so we will be forced to continue to also use [Lerna](https://lerna.js.org/)
* Bad, because it is currently in [maintenance mode](https://github.com/yarnpkg/yarn/issues/8583) and as such not a good
bet for the future
* Bad, because we did not have a so [good experience back in the days](https://tuleap.net/plugins/tracker/?aid=9948) and
it is still true that the performance is not really our main issue (and the diff with npm 7 is negligible at best)

### Migrate to [Yarn 2+ / Berry](https://yarnpkg.com/)

* Good, because it is one of the major JS package managers
* Bad, because the workspace feature does not work so well for our structure which will force us to still edit the
generated lockfiles and/or to still use [Lerna](https://lerna.js.org/)

### Migrate to [pnpm](https://pnpm.io/)

* Good, because we can make the workspace feature work for our situation without hacks
* Good, because we would not need to use [Lerna](https://lerna.js.org/) anymore
* Good, because the way the dependencies are installed leads to a stricter dependencies management
* Good, because, while it is not our main pain points, it saves disk space and it is quite faster than npm to install
all our dependencies
* Bad, because it is less used than the other considered options. It is however gaining traction lately
[[0]](https://github.com/pnpm/pnpm.github.io/blob/5665cf01827779fe0f643d62b416dba7c632ef83/users.json)
[[1]](https://github.com/pnpm/pnpm.github.io/blob/5665cf01827779fe0f643d62b416dba7c632ef83/docs/workspaces.md#usage-examples)
and is one of the three supported package managers of the recently introduced [Corepack](https://nodejs.org/api/corepack.html).
