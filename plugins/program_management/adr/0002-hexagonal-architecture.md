# Hexagonal Architecture in Program Management plugin

* Status: accepted
* Date: 2021-09-20

Technical Story: [epic #16683 Program Management][0]

## Context and Problem Statement

In order to achieve the mission goals of Program Management, we expect strong usage of the Tracker plugin's API (`Fields`, `Trackers`), the Agile Dashboard plugin's API (`Plannings`), and the Tuleap Core API (`\PFUser, \Project`). We know that these APIs can be a source of issues and architectural problems from our previous experience (Cardwall plugin, later Taskboard plugin, etc.). How can we protect our new API in the Program Management plugin from "corruption" ? How can we protect ourselves from the massive [god-objects][2] found in Trackers or Tuleap Core ?

## Decision Outcome

We have chosen to try [Hexagonal Architecture][3] with the goal of protecting our new plugin's API, originally from Tracker plugin. Later, we decided to also protect our API from Tuleap Core (especially from `\PFUser` usage).

### Domain and Adapters

There are two main namespaces in Program Management: `Tuleap\ProgramManagement\Domain` and `Tuleap\ProgramManagement\Adapter`.

Domain namespace holds all the code related to Program Management specifics. In the Hexagonal Architecture, it is our "hexagon". Domain must **never** have dependencies outside of itself. Especially, code in the Domain namespace must **never** depend on code in the Adapter namespace.

Adapter namespace holds all the code that allows our plugin to communicate with the outside world. This includes:
- Database operations. Data Access Objects (DAOs)
- Web UI. Presenters
- REST API
- XML Import / Export
- Dependencies on other Plugins (`Tracker`, `Artifact`, `Changeset`, `Planning`, etc.)
- Dependencies on Tuleap Core (`\PFUser`)

Domain code communicates with the Adapters via interfaces. This kind of Interfaces is always in the Domain namespace. There can also be interfaces in the Adapter namespace, but the Domain code can never use those.

For example, if you want to verify something in the database, you must add an interface in Domain code. Then, you must create a DAO in Adapter namespace that will implement the new interface. Your Domain code must **never** call the DAO directly, it **always** calls the interface. Then, in the entrypoint, you create a new DAO and pass it to your Domain class.

### Stubs

Having our Domain code depend on interfaces for all side effects, like UI, Database and other plugins is really handy for unit tests. See [ADR-0004 Writing unit-tests without mocks][6] for details.

### Value-object interfaces and Proxies

Sometimes, we want to build our Domain classes from Tuleap Core classes (Events) or from other plugins (`Field`). Since we cannot depend on those, we might be tempted to take only primitive values as parameter but this leads to [primitive obsession][5].

Another way is to use a Value-object interface: an interface that describes the getters we expect to have on our object. For example `getId()`, `getLabel()`, etc. This interface lives in the Domain namespace, so all Domain classes can depend on it in their parameters. Its implementation is usually called a Proxy (see our [glossary][1]) and since it manipulates classes outside the Domain, it lives in the Adapter namespace. Only Adapters may create Proxies.

For example, if you want to handle the `ArtifactUpdated` event from the Tracker plugin, your Domain classes cannot depend on it directly. You should create a new interface like `ArtifactUpdatedEvent` that exposes the necessary getters. Then, write an `ArtifactUpdatedProxy` class that implements it in Adapter namespace. The `program_managementPlugin` will create the Proxy from the raw `ArtifactUpdated` event. This breaks the dependency from the Domain to the other plugin, as now the Domain only depends on interfaces inside itself.

The interface does not need to match exactly the external object ! For example, instead of making the same naming mistakes like `userCanSee()`, we control the naming here and can write `canUserSee()`. In case of Events, we can pass Domain objects and the Proxy can convert them to whatever the Event expects. For example instead of a method `addArtifact(Artifact $artifact)`, our interface can have a method `addFeature(Feature $feature)`. The Proxy's job will be to convert a `Feature` into an `Artifact`.

### Entrypoints

Entrypoints to the plugin like the `program_managementPlugin` class or Restler `Resource` classes are considered to be Adapters. They are not in the Adapter namespace for technical reasons. They are allowed to create Proxies and Adapters. They are responsible for instantiating the correct Adapters and Domain classes.

### Recommendations and rules

- Domain code must **never** depend on Adapters. It must **always** depend on interfaces that are in the Domain. Interfaces are implemented by Adapters.
- Domain code must **never** depend on other plugins or Tuleap Core. It must always use Domain interfaces instead.
- Interfaces should be slim, in almost all cases an interface should only have one method. It makes it easier to write small Stubs. There is no problem at all having one Adapter, like a DAO, implement ten interfaces. Facade interfaces can even be created: interfaces that group together many smaller interfaces.
- Interfaces should have a verb in their name. It lets us distinguish the interface `VerifySomething` from its implementation `SomethingVerifier`. See our [glossary][1] for naming recommendations for interfaces (like `Verify<something>`, etc.).
- Stubs should be as simple as possible.
- Only use Mocks when writing tests for Adapters. In all other cases, Stubs must be preferred.
- Avoid writing big Adapters. It is tempting to write just a giant Adapter that calls DAOs, makes verifications, etc. "Plumbing" should be in the Domain, even if all operations are hidden behind interfaces. The order and sequence of operations is still business logic and belongs to the Domain.

Keep in mind that we have adopted these rules and recommendations gradually, so these rules may still be broken in existing code. We must strive to fix these instances.

### Positive Consequences

* It makes us write a "compatibility" layer between other plugins and our code that protects it. If we change the other plugin's code, only the Adapters should change.
* It allows us to manipulate Tuleap concepts like Tracker with a naming that makes sense in our Domain. Program Increment and Features are both Artifacts from Tracker, but in our plugin they have a distinct role and meaning.
* It reduces cognitive load. Responsibilities are better split.
* Tests are less coupled to implementation.
* We can write _Overlapping Sociable Tests_.
* The design made us fix the previous flaws: we previously had "wrapper" objects with methods like `ProgramTracker::getFullTracker()`. This is a flaw in the hexagonal architecture, as nothing prevents Domain code from handling `Tracker` object. The design pushed us to fix this in order to add new features.

### Negative Consequences

* It produces _a lot_ of files. Many Interface files, and as many Stubs.
* It is very hard to enforce and implement _a posteriori_ on existing code. It is basically faster to rewrite the existing code.

## Links

* Program Management [epic][0]
* Definition of terms: [glossary][1]
* Testing Without Mocks: A Pattern Language [blog post][4]
* [ADR-0004 Writing unit-tests without mocks][6]

[0]: https://tuleap.net/plugins/tracker/?aid=16683
[1]: <./glossary.md>
[2]: https://en.wikipedia.org/wiki/God_object
[3]: https://en.wikipedia.org/wiki/Hexagonal_architecture_(software)
[4]: https://www.jamesshore.com/v2/blog/2018/testing-without-mocks
[5]: https://martinfowler.com/bliki/DataClump.html
[6]: <./0004-mock-free-tests.md>
