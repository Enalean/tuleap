---
status: accepted
date: 2024-12-13
decision-makers: Thomas GORKA, Joris MASSON
consulted: Kevin TRAINI, Nicolas TERRAY, Clarisse DESCHAMPS
informed: Thomas GERBET, Clarck ROBINSON, Marie Ange GARNIER, Manuel VACELET, Martin GOYOT
---

# Avoid using stores in Vue apps

## Context and Problem Statement

[request #44064 ADR: Avoid using stores in Vue apps][0]

* https://gerrit.tuleap.net/c/tuleap/+/11947
* https://gerrit.tuleap.net/c/tuleap/+/11971

In the course of July 2018, we started to use [Vuex][1] for the first time. [Vuex][1] is a library for a central application store. At that time, we found it convenient because Vue2 did not allow an easy communication between children components to their
parent or sibling components. With stores, it became easy because we could store any information we wanted, put all the logic in them, and access them whenever needed. However, was it actually a good thing?

_Spoiler alert_: the recent migrations from Vue2 to Vue3 with composition API have proven us that it was not.

### Stores contain too many useless things

Here is a non-exhaustive list of what we can find in our older Vue apps using stores:
Constants, values used only by one component, the current date, modal instances, full REST payloads, you name it…

Most of these values do not need to be in a store:
- Constants: Should be exported from a dedicated file, and imported where needed.
- Values used by only one component: Should be a local variable in the component.
- The current date: Can be computed easily when needed.
- Modal instances: Should be kept in the components instantiating them.
- Icon names: UI thing. Should not be out of a component.
- Request states (`is_success`, `is_error`): Those are the results of an action. It should be the component's role to do something depending on the returned Promise/ResultAsync (display an error message or a success feedback).
- A REST payload: Does it really need to be stored? Can't we navigate its structure in a dedicated component instead?

### Stores contain all the apps logic

Store engines like [Vuex][1] or [Pinia][2] encourage us to move all the logic into them. Their design pushes us to structure things by splitting them into states, getters, mutations, and actions.

This is bad because:
- When some breaking changes are introduced by a [Vuex][1]/[Pinia][2] version bump, it is painful to fix them (Vue2 -> Vue3 migration).
- Stores end up being god objects, which does not respect the [Single Responsibility Principle][5].
- The dependencies to the stores spread, and almost all the components end up depending on them.
- Unit test setups tend to be more complex since a store usually manages several features in addition to loading elements, updating them etc.

### Stores mess with apps architecture

Given that they contain all the app's logic, components are built around the stores: We do not pass any props, we only query the stores and use them.

Stores are really the center of the applications they belong to, and this is bad. There is no clear separation between
the different layers of the apps (data / communication / UI) since they are all merged in the same place:
1. They make API calls.
2. They transform/update the data.
3. They tell the UI what to do (app is loading, there is an error, something is being saved or has been saved).

One bad habit we had when creating apps from scratch was to initialize an empty store alongside an empty app while we do not yet need one. We then proceed to fill it progressively as user stories follow one another without thinking much about the scopes of the features inside components.

## Decision Drivers

* We should apply the [Single Responsibility Principle][5] also on the front-end. This means that we shouldn't mix REST API, business logic and UI display in a single part of the application.

## Decision Outcome

Avoid using centralized stores.

Since the adoption of stores, there have been several improvements to Vue itself (with Vue 3) that have greatly simplified the code design of apps and inter-component interactions:
1. We can use [provide/inject][4].
2. Vue components can have more than one root element.

Those two features make it much more simple to design apps architecture without any stores.

### Sharing data without a store

When designing an app architecture, we should start without any store. The Vue app should be self-sufficient at the beginning of a new project.

The data should be the driver to decompose the application in components and classes.

When considering a piece of data, we should ask ourselves what is its context:
1. Most of the time, it is local to a single component. Keep it there.
2. Sometimes, data must be shared between parent/children components. Use props to give data to the children.
3. Data can belong to a group of components with more than one level, or components that are siblings. Use local [provide/inject][4] to share the data, or use an event-emitter architecture to communicate between components.
4. Rarely, the data's context is the whole application. Usually, this data _does not change_ during the application's lifecycle. For example, the current user identifier, the user locale, the current project identifier. In this case, use app-level [provide/inject][4] to share the data.

### How can we design an app without a store?

We could interpret that components should do everything, but that should be avoided, we do not want to trade god-objects in stores for god-objects in components. Big components are hard to test and hardly tested. Instead, we should also use tools like [provide/inject][4] and event architecture to split responsibilities.

Components are only responsible for the display of the User Interface and for handling interactions (button clicks, opening modals, etc.). Data related to the UI (`is_success`, `is_loading`, etc.) should always live in components.
We must write small class-like objects to deal with the business logic: calling the REST API, transforming business data, formatting payloads for the backend to save data, etc. We should use the same principles to "inject" our class-like objects in components:
1. First use props.
2. If it needs to be shared, use local [provide/inject][4].
3. If it's used in the whole application, use app-level [provide/inject][4].

### Consequences

* Good, because applications are simpler, easier to unit-test, and more maintainable.
* Bad, because we will have to refactor existing stores to remove them progressively.

### Confirmation

* There are no more dependencies on [Vuex][1] or [Pinia][2] in Tuleap.
* There are no homemade stores built with [Composables][3].

## More Information

Using [Composables][3] as a replacement for [Vuex][1] or [Pinia][2] is also not desirable. At first, we only put [refs][6] in them. Then we add setters/getters because it is more convenient to mutate/query their values. Lastly we end up adding business logic through functions doing REST calls depending on the `refs`. They are slowly becoming god objects, and this is what we want to prevent.

[Composables][3] can end up being dependent of one another, making it harder to understand, evolve, refactor.

It is ok to use [Composables][3], but without any business logic. For instance: a reactive collection of items, a config object.

## Links

* [Vuex][1] centralized store library.
* [Pinia][2] centralized store library.
* [Composables][3] pattern in VueJS.
* [request #41467 Refactor artidoc's composable architecture][8] for an example where Composables were in fact used like stores.
* [ADR-0003 Soft ban on ES2015 classes][7] in the Artifact Modal. It explains the "class-like" pattern.

[0]: https://tuleap.net/plugins/tracker/?aid=44064
[1]: https://vuex.vuejs.org/
[2]: https://pinia.vuejs.org/
[3]: https://vuejs.org/guide/reusability/composables.html#composables
[4]: https://vuejs.org/guide/components/provide-inject
[5]: https://en.wikipedia.org/wiki/Single-responsibility_principle
[6]: https://vuejs.org/api/reactivity-core.html#ref
[7]: ../../plugins/tracker/scripts/lib/artifact-modal/docs/decisions/0003-ban-es2015-classes.md
[8]: https://tuleap.net/plugins/tracker/?aid=41467
