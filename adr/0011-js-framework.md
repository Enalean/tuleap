# State of JS frameworks in the Tuleap codebase

* Status: accepted
* Deciders: Thomas Gerbet
* Date: 2022-03-17

## Context and Problem Statement

Multiple JavaScript frameworks have been used over the years in Tuleap codebase. The role of this document is to state the
preferred solution when creating a new app and to describe the others situations currently existing in the codebase.

This document is expected to be revised/superseded in the future.

## Current targets

### [Vue 3](https://vuejs.org/) with components written in TypeScript using the [Composition API](https://vuejs.org/guide/introduction.html#composition-api)

New JS app requiring the use of a framework are expected to use [Vue 3](https://vuejs.org/) with TypeScript and
the [Composition API](https://vuejs.org/guide/introduction.html#composition-api).

Components must be written using [Vue <abbr title="Single-File Component">SFC</abbr>](https://vuejs.org/guide/scaling-up/sfc.html).

[Volar](https://github.com/johnsoncodehk/volar) / [vue-tsc](https://github.com/johnsoncodehk/volar/tree/master/packages/vue-tsc)
must be used to typecheck the code.

If complex state management is needed, [Pinia](https://pinia.vuejs.org/) must be used.

### Alternatives

On [specific situations](../plugins/tracker/scripts/lib/artifact-modal/adr/0001-choice-of-templating-engine.md) other
lightweight alternatives might be preferred. Those situations must be evaluated on a case by case basis.

## Legacy targets

### [Vue 2](https://v2.vuejs.org/) with components written in TypeScript using [Vue Class Component](https://class-component.vuejs.org/)

New components must be written using the [Composition API](https://github.com/vuejs/composition-api).
Existing components should be migrated to the Composition API in order to make it possible to migrate to Vue 3.

Components that might still be written in plain JavaScript should be migrated to TypeScript and the Composition API.

Migration to Vue 3 should be considered the primary goal. Migrating from [Vuex](https://vuex.vuejs.org/) to Pinia is
considered a secondary objective which is not critical to tackle yet, even if it means adding more "Vuex code" in the short
term.

### [AngularJS](https://angularjs.org/)

AngularJS is considered end-of-life upstream. New Tuleap features are not supposed to add more AngularJS code to the
codebase.

Specific migration plans should be crafted for each app to determine the most appropriate solution. If new features are
required in an existing AngularJS app and migrating to something else is not yet possible, care must be taken to increase
as little as possible our dependency to AngularJS.