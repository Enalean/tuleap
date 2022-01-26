# Hexagonal Architecture

* Status: accepted
* Deciders: Joris MASSON, Thomas GORKA
* Date: 2022-01-26

Technical Story: [story #24968 [modal] have the list of linked artifacts][1]

## Context and Problem Statement

The Artifact Modal is used throughout Tuleap. Since it supports the creation and edition of any Tracker Artifact, it must follow the many business rules of Tuleap Trackers. The Tracker plugin has no dependency on other plugins, so we don't have to worry about that.

Usually, in front-end code, we rely on a Framework to keep things organized. Frameworks often have some concepts to separate code handling the DOM from code handling HTTP requests. It is a good idea to keep them separate because if we have to change the way the HTTP requests are done, it will avoid completely breaking the UI as well. We are [moving away from frameworks][5] in the Artifact Modal codebase, so how can we design our code to prevent mixing those concerns ? How can we better organize the many Tracker business rules, which are often mixed with UI components ?

## Decision Outcome

After successful adoption of [Hexagonal Architecture in Program Management][2], we have chosen to try it again in front-end code. The goals are to enforce the separation of code writing the DOM from code making HTTP requests and to better separate business rules from the UI.

### Domain and Adapters

There are two folders in the Artifact Modal: `domain` and `adapters`.

Domain folder holds all the code related to the Artifact Modal's business rules. Domain must **never** have dependencies outside of itself. Especially, code in the Domain namespace must **never** depend on code in the Adapter folder.

The Adapter folder holds all the code that allows the Modal to communicate with the outside world. This includes:
- UI / DOM handling
- HTTP (REST) API
- Memory / Session / Local storage
- Routing / Navigation

Domain code communicates with the Adapters via interfaces. This kind of Interfaces is always in the Domain folder. There can also be interfaces in the Adapter folder, but the Domain code can never use those.

For example, if you want to query something in the REST API, you must add an interface in Domain code. Then, you must create an `APIHandler` that will implement the new interface in the Adapter/REST folder. Your Domain code must **never** call the `APIHandler` directly, it **always** calls the interface. Then, in the entrypoint, you create a `APIHandler()` and pass it to your Domain factory function.

### Stubs

Having our Domain code depend on interfaces for all side effects, like UI, HTTP requests and Memory storage is really handy for unit tests. See the [ADR for Writing unit-tests without mocks][4] for details. It was written for PHP but the same principles can apply to TypeScript.

Instead of repeating calls to `jest.spyOn()`, we can use Stub factory functions. Stubs should be in the `tests/stubs/` folder so that it is easy to find them. Stubs implement the interfaces from the Domain with a simple implementation, for example one that always return a result.

### Recommendations and rules

* Please see the matching section in the [ADR for Program Management plugin][2]. The rules are the same.
* In addition, we should avoid using `class` or `this` keywords. [TypeScript can match the encapsulation and expressiveness][3] of `class` with factory functions (closures), plain objects and interfaces. `class` are harder to minify and can create traps when using inheritance. Many developers struggle with the correct usage of `this` in relation to functions or arrow functions. That is why we should avoid using them.

Keep in mind that we have adopted these rules and recommendations gradually, so these rules may still be broken in existing code. We must strive to fix these instances.

### Positive Consequences

* It forces us to separate writing to the DOM from REST API calls.
* It makes us write a "compatibility" layer between our code and commonly used libraries that protects our code. If we decide to change the UI / DOM library or the REST API library, only the Adapters should change.
* It reduces cognitive load. Responsibilities are better split.
* Tests are less coupled to implementation.

### Negative Consequences

* It produces _a lot_ of files. Many Interface files, and as many Stubs.
* It will take a long time to adapt the existing code to this architecture. The two patterns will coexist for a long time: "hexagonal" code and "non-hexagonal" code.

## Links

* [ADR for Hexagonal Architecture in the Program Management plugin][2]
* [ADR for Writing unit-tests without mocks][4]
* [ADR-0001 Choice of templating engine][5]

[1]: https://tuleap.net/plugins/tracker/?aid=24968
[2]: <../../../../../program_management/adr/0002-hexagonal-architecture.md>
[3]: https://medium.com/@vapurrmaid/should-you-use-classes-in-javascript-82f3b3df6195
[4]: <../../../../../program_management/adr/0004-mock-free-tests.md>
[5]: <./0001-choice-of-templating-engine.md>
