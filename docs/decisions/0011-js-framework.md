# State of JS frameworks in the Tuleap codebase

* Status: accepted
* Deciders: Thomas GERBET
* Date: 2022-03-17

## Context and Problem Statement

Multiple JavaScript frameworks have been used over the years in Tuleap codebase. The role of this document is to state the
preferred solution when creating a new app and to describe the other situations currently existing in the codebase.

This document is expected to be revised/superseded in the future.

## Current targets

### [Vue 3][0] with components written in TypeScript using the [Composition API][1]

New JS apps requiring the use of a framework are expected to use [Vue 3][0] with TypeScript and the [Composition API][1].

Components must be written using [Vue <abbr title="Single-File Component">SFC</abbr>][2].

[vue-tsc][3] must be used to typecheck the code.

Complex state management is probably not needed (see [ADR-0033][9]), but if necessary, [Pinia][4] must be used.

### Alternatives

On [specific situations][5] other lightweight alternatives might be preferred. Those situations must be evaluated on a case-by-case basis.

## Legacy targets

### [Vue 2][6] with components written in TypeScript using [Vue Class Component][7]

New components must be written using the [Composition API][1].\
Existing components should be migrated to the Composition API in order to make it possible to migrate to Vue 3.

Components that might still be written in plain JavaScript should be migrated to TypeScript and the Composition API.

The migration to Vue 3 should be considered the primary goal. [Migrating from Vuex to alternatives][9] is considered a secondary objective which is not critical to tackle yet, even if it means adding more "Vuex code" in the short term.

### [AngularJS][8]

AngularJS is considered end-of-life upstream. New Tuleap features are not supposed to add more AngularJS code to the
codebase.

Specific migration plans should be crafted for each app to determine the most appropriate solution. If new features are
required in an existing AngularJS app and migrating to something else is not yet possible, care must be taken to increase
as little as possible our dependency to AngularJS.

## More Information

* [ADR-0033 Avoid using stores in Vue apps][9]

[0]: https://vuejs.org/
[1]: https://vuejs.org/guide/introduction.html#composition-api
[2]: https://vuejs.org/guide/scaling-up/sfc.html
[3]: https://github.com/vuejs/language-tools/tree/master/packages/tsc
[4]: https://pinia.vuejs.org/
[5]: ../../plugins/tracker/scripts/lib/artifact-modal/docs/decisions/0001-choice-of-templating-engine.md
[6]: https://v2.vuejs.org/
[7]: https://class-component.vuejs.org/
[8]: https://angularjs.org/
[9]: 0033-avoid-using-stores-in-vue-apps.md
