---
status: accepted
date: 2025-03-07
decision-makers: Joris MASSON
consulted: Marie Ange GARNIER, Thomas GORKA, Nicolas TERRAY, Thomas GERBET
informed: Clarck ROBINSON, Kevin TRAINI, Manuel VACELET, Clarisse DESCHAMPS, Martin GOYOT
---

# Simulate network responses for front-end components

## Context and Problem Statement

Since the adoption of Storybook in [ADR-0027][1], we have added stories for all the components of TLP that we use to build Tuleap's user interface. Now, it would be nice to go further and build stories for bigger components, for example the [Link field][2] that is going to be used both in the Artifact Modal and in the Artifact full page. Having a story for bigger components would make it much easier to work on them, as we typically avoid having 3 or 4 levels deep "watch" commands. We could also trigger hard-to-reach states much more easily, without needing real data. However, one critical part of such components is that they often (always?) work with network requests. Without network, the Link field is useless, you can barely test anything.
How can we simulate REST API responses, so that we can build stories for the components that need them ?

[story #41466 Have new Artifact Links in Artifact View][0]

## Decision Drivers

* Storybook Stories should not be coupled more than necessary to their component. A Story should not have deep knowledge of a component's internals, other than what is necessary to set it up, mount it and configure it.

## Considered Options

* Stubs of REST adapters
* Use [Mock Service Worker][3]

## Decision Outcome

Chosen option: "Use Mock Service Worker", because it avoids deep coupling between the Story and the component internals.

### Consequences

* Good, because we have the necessary tools to write Stories for more complex components that depend on network responses to function. It may open the door to automated integration tests for our frontend components.
* Bad, because it's one more dev dependency to keep up-to-date.

### Confirmation

All new Stories for components that require network (REST API, etc.) should use [Mock Service Worker][3] to simulate a fake backend.

## Pros and Cons of the Options

### Stubs of REST adapters

In [ADR 0002 Hexagonal Architecture][5] for the Artifact Modal, we decided to use Hexagonal Architecture while refactoring the modal. The Link field is organized this way, with separate adapters for the network communication with REST. Given this context, we could leverage this and replace all the API clients of the Link field with stubs. In the Story, we would pass only stubs to our field, so that the Story would never actually make any real `fetch` network requests, and would receive our "fake" prepared data.

* Good, because it's a strategy we are already using in [sociable][6] unit tests.
* Bad, because it requires deep knowledge of the internals of the component under test. In the Link field, the interfaces for network requests return Domain objects. It means we must export all meaningful Domain objects, so that the Story can create them and pass them to the Stubs. It makes the story tied to the implementation of the component.
* Bad, because such Stubs cannot be shared. Since interfaces (rightly) return Domain objects, each component will have different Domain objects. It means we cannot write once a Stub that returns "Artifacts" and reuse it, because "Artifact" is going to have a different shape depending on the component.

### Use [Mock Service Worker][3]

Mock Service Worker works at the network level of the browser. It uses a standard browser technology called the [Service Worker API][7] to intercept network requests. We can define the routes we want to intercept, like a real backend router. We can write request handlers to handle the requests, just like a real Node.js backend. We can output JSON, or errors, or whatever. See Mock Service Worker's [Philosophy][4] for more information.

* Good, because since it works at the request and response level, it depends only on REST API types and constants. It never needs to know the Domain objects, and we can keep them private and internal.
* Good, because since many JSON responses of the REST API have similar shapes, it means we can write some code to simplify building common JSON representations. For example, we can write a builder for Projects, for Users, for Trackers, etc. We could even reuse those builders more widely in unit tests.
* Good, because it can also be used in [Node.js processes][8], so we may reuse it in frontend unit tests instead of mocking `@tuleap/fetch-result`.
* Neutral, because we have no experience with it and will have to learn.

## More Information

This decision should be re-visited in the future to see if it is still the best available option.

* [ADR-0027 Choice of tool to present the Components documentation][1]

[0]: https://tuleap.net/plugins/tracker/?aid=41466
[1]: 0027-component-documentation.md
[2]: ../../plugins/tracker/scripts/lib/link-field/README.md
[3]: https://mswjs.io
[4]: https://mswjs.io/docs/philosophy
[5]: ../../plugins/tracker/scripts/lib/artifact-modal/docs/decisions/0002-hexagonal-architecture.md
[6]: https://www.jamesshore.com/v2/projects/nullables/testing-without-mocks
[7]: https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API
[8]: https://mswjs.io/docs/integrations/node
