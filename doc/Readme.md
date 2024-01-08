# Developer guide

Tuleap is a Free and Open Source software developed since 2001 under
GPLv2 license. Contributions are welcomed, on Tuleap Core, on plugins,
in documentation, etc.

Development of a Tuleap extension, outside of the main development tree
is hard and you are likely to shoot yourself in the foot if you do so.
The main reason is that Tuleap internal API is not guaranteed, it can
change at any moment for any reasons.

REST API & Webhooks, on the other hand are very carefully maintained and
we ensure, as much as possible the backward compatibility. We (the core
team) strongly recommend to look this way for your developments.

This guide will gives you the insights to start your contributions to
Tuleap:

-   Setting up your environment
-   Push your code for review to integrators
-   Understand Tuleap internals

You can also find help for your dev related questions on the [chat in
the #general channel](https://chat.tuleap.org/).

## Disclaimer

Documentation of a living tool is hard to achieve and, in case of
doubts, the source code is always the reference.

When working with sources, you must look closely to [Architecture
Decision
Records](../adr/index.md)
that will give insights on the evolutions of the code base and what is
expected / current norm.

## Getting started

Getting started, what you need to know to setup your environment and
push your code

* [Contrib](contrib.md)
* [Quick start](quick-start.md)
* [Patches](patches.md)
* [Coding standards](coding-standards.md)
* [Expected code](expected-code.md)
* [Development tools](development-tools.md)

Development 101, what you need to know

* [Front end](./front-end.md)
* [Back end](./back-end.md)
* [Tests](./tests.md)
* [Internationalization](./internationalization.md)
* [Integrators](./integrators.md)

Advanced topics

* [Release](./release.md)
* [LDAP](./ldap.md)
* [Realtime](./realtime.md)
* [Trackers](./trackers.md)
* [Gerrit](./gerrit.md)
* [Project background](./project-background.md)
* [Gitlab](./gitlab.md)
* [Untrusted code execution](./untrusted-code-exec.md)
