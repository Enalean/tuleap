# Choice of JavaScript Bundler

* Status: accepted
* Deciders: Thomas GERBET, Joris MASSON
* Date: 2021-10-20

Technical Story: [request #23415 Allow to bundle app with Vite][0]

## Context and Problem Statement

In a [previous decision][2] (before writing ADRs), we chose to use [webpack][3] as our JavaScript module bundler. It was one of the first tools of this kind and ushered us into the world of ECMAScript modules: we could finally split our JavaScript apps into clean, isolated modules. However, configuring webpack is very complicated. Since then, new tools have appeared that can do what webpack cannot: start from scratch while learning webpack's lessons. One such tool is [Vite][4]. The question is thus: should we change our default module bundler tool to [Vite][4] or stick with [webpack][3] ?

## Decision Drivers

* Keeping the frontend build system running costs a lot of time.
* New Applications and Libraries are mostly started by copying the configuration from another one.

## Considered Options

* Keep using [webpack][3]
* Start using [Vite][4]

## Decision Outcome

[Vite][4] is the default module bundler for new Applications and Libraries (as defined by [ADR-0016][6]). Some contexts will still require [webpack][3]: for example AngularJS applications, legacy concatenated scripts, etc.

### Positive Consequences

* Fewer configuration files, easier configuration.
* Slightly faster build time
* Ability to defer JavaScript execution in the browser by using `<script type=module>`.

### Negative Consequences

* We will have two different module bundlers to maintain for quite a long time, until we decide to keep only one, and do the work to make that happen.

## Pros and Cons of the Options

### Keep using [webpack][3]

* Good, because webpack is very flexible and its plugin system has never failed us: we use it to build both old AngularJS apps and the latest technologies (at the moment of writing) such as Vue 3.
* Good, because it is used by the majority of the JS community.
* Bad, because configuring webpack is very complicated: it handles lots of concepts such as `rules`, `plugins` and `loaders`. Putting everything in the right place is hard. We have written a dedicated library to make it easier for devs, and even then, it's not that simple.

### Start using [Vite][4]

* Good, because Vite has far easier configuration. Most of our explicit configuration for webpack comes as default for Vite. Its configuration file is also in TypeScript, which means we get auto-completion for it.
* Good, because Vite is pushed and maintained by the team behind [Vue.js][5]. The quality of maintenance of Vue.js has been very high so far, so we can expect the same for Vite.
* Good, because it handles the majority of our needs by default: bundling TypeScript, SCSS and images works out of the box. It is not very hard to load `.po` files (translations), and we have the same constraint for webpack.
* Bad, because it is still relatively new.

## Links

* Influences [ADR-0014: JS unit test runner][1]
* [ADR-0016: Independent libraries for shared frontend code][6]

[0]: https://tuleap.net/plugins/tracker/?aid=23415
[1]: 0014-js-unit-test-runner.md
[2]: https://tuleap.net/plugins/tracker/?aid=10195
[3]: https://webpack.js.org/
[4]: https://vitejs.dev/
[5]: https://vitejs.dev/team.html
[6]: 0016-frontend-libraries.md
