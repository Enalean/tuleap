# Definition of terms used in Program Management plugin

* Date: 2021-09-21

Technical epic: [epic #16683](https://tuleap.net/plugins/tracker/?aid=16683): Program Management

## Context and Problem Statement

During the development of the Program Management plugin, we used different terms which are not understandable by everyone.

This ADR aims to explain each term to have common knowledge. These terms are used in front end and back end of the plugin.

## Glossary

#### Feature

Artifacts that can be planned in `Program Increment`, respecting the `Plan`. That can be an Epic ...

#### Iteration

An Artifact in the `Program` project that represents a subdivision of a `Program Increment`. `Program Increments` are split into `Iterations` and `User Stories` in the Program Increment's backlog are then planned in Iterations by the `Team` members.

#### Mirrored Iteration

The duplication (mirror) of an `Iteration` in `Team` projects. It is created in the Milestone Tracker of the AgileDashboard Planning at level 1 (child Planning of the Root Planning). It is usually a Sprint Tracker, but `Teams` can change this to any kind of Tracker.

#### Mirrored Timebox

A generic term for `Mirrored Program Increment` and `Mirrored Iteration`.

#### Mirrored Program Increment

The duplication (mirror) of a `Program Increment` in `Team` projects. It is created in the Milestone Tracker of the Root (top-level) AgileDashboard Planning. It is usually a Release Tracker, but `Teams` can change this to any kind of Tracker.

#### Plan

Different tracker that can be used to plan in a `Program Increment`. That can be Feature Tracker, Bug Tracker, ...

#### Program

A Project that holds `Program Increments`, `Iterations` and `Features`. The `Program` project will aggregate `Team` projects. A `Program` can have many `Teams` attached to it.

#### Program Backlog

The Program Backlog will display the `Features` that'll need to be planed during PI planning. The `Features` will be split in `User Stories`, and `User Stories` will be planned in `Release` and `Sprint` of `Team` projects.

#### Program Increment

An Artifact created in the `Program` that represents a period of time. Work is scheduled in successive `Program Increments` to help agile organizations deliver software in an incremental way. `Program Increment` is "mirrored" in all `Teams` of its `Program`.

#### Team

Project linked to `Program` project. The `Team` project contains `User Stories`. `User stories` can be local or can be inherited from `Feature`.

#### Timebox

A generic term to describe both `Program Increment` and `Iteration`.

#### User Story

Artifact in `Team` project linked as child to `Feature`. They will be planned automatically during the PI planning, the users will plan `Feature` and `User Stories` linked to `Feature` will be planned by inheritance. That can be an Activity, Request, Bug, ...

### Keep it in mind

As it's an implementation of SAFe, we use the same terminology as the framework.
Program management is a complex plugin, so we don't want to complicate it further with terms we don't understand.

Using framework terms simplifies development and lets you know which object you are manipulating.
But the object you are manipulating might not contain exactly what it points to (for example, a "Bug" might be in the object named "User Story ").

## Technical Terms

#### Adapter

`Adapters` implement interfaces from the `Domain`. Adapters can call classes from the `Domain`. They are allowed to do Database operations, call other `Adapters`, have dependencies on classes from other plugins (like `Tracker`) or Tuleap Core objects (like `\PFUser`). See [ADR-0002 Hexagonal Architecture][1].

Adapters are always in the `Tuleap\ProgramManagement\Adapter` namespace.

#### Delete`<something>`

Interfaces named `Delete` have one method that returns `void`. They usually take an object or a primitive in parameter and delete something in storage (database).

#### Domain

`Domain` holds all the plugin's business logic. Classes from the `Domain` must **never** have dependencies on `Adapters`. They must **never** use or depend on classes from other plugins (like `Tracker`), or Tuleap Core objects (like `\PFUser`). See [ADR-0002 Hexagonal Architecture][1].

Domain code is always in the `Tuleap\ProgramManagement\Domain` namespace.

#### `<something>`Handler

`Handlers` are classes that are responsible for dealing with a matching `Event`. For example, the class responsible for responding to the `ArtifactUpdatedEvent` will be `ArtifactUpdatedHandler`.

#### `<something>`Proxy

A Proxy is an implementation of a `value-object` interface, like `UserIdentifier`. The Proxy class lies in the `Adapter` namespace, which means only other `Adapters` may call it directly.

`Proxies` are a way to adapt objects from Tuleap Core or from other plugins to Domain Interfaces. For example, there is a `UserProxy` class that implements `UserIdentifier` and that can be created from a `\PFUser` class. Since it depends on `\PFUser`, it **must** be in the Adapter namespace (see [ADR-0002 Hexagonal Architecture][1]).

Proxies are always in the `Tuleap\ProgramManagement\Adapter` namespace.

It is okay to use Proxies in Unit Tests of Domain classes. Unit tests are not bound to the same rule as production code. Alternatively, you can write a `Stub` for the value-object interface.

#### Retrieve`<something>`

Interfaces named `Retrieve` have one method that returns one object (or null). They may also throw exceptions. Contrary to `Search`, they do not return arrays.

#### Search`<something>`

Interfaces named `Search` have one method that returns an array of objects. They may return an empty array `[]`.

They are named after the convention of naming methods `search<Something>()` in Data Access Objects.

#### Store`<something>`

Interfaces named `Store` have one method that returns `void`. They usually take a single object in parameter and save it in storage (database).

#### `<something>`Stub

A test-only implementation of an Interface. Contrary to `Builders`, `Stubs` always implement an interface. Most of the time, they implement an operation like `Retrieve` something or `Verify` something. Sometimes, they implement a `value-object` interface like `TrackerIdentifier` or `UserIdentifier`. They should be kept as simple as possible. Their static methods usually are prefixed by `with`: `withTracker()`, `withUser()`, etc.

In the case of `value-object` interfaces, we create Stubs to make it easier to build them. It is easier to build a `UserIdentifierStub` with just an `int` than to build a `UserProxy`, because then we must also build a `\PFUser`.

Stubs are always in the `Tuleap\ProgramManagement\Tests\Stub` namespace. Stubs are always prefixed by the interface name they implement and suffixed by `Stub`. For example: `VerifyIsProgramStub`, `UserIdentifierStub`.

#### Test Builder

A class with static methods that allows to build objects that are repeated a lot across the tests (for example `ProgramIdentifier`) or to build objects that are quite complicated and require a lot of `Stubs` and/or many steps to build (for example `MirroredTimeboxChangeset`). Contrary to `Stubs`, they do not implement any interface.

`Test Builders` are always in the `Tuleap\ProgramManagement\Tests\Builder` namespace.

They let us depend only on the builder and not on many stubs. For example, instead of adding dependencies on `VerifyIsProgramIncrementStub` and `VerifyIsVisibleArtifactStub` in 30 tests, we only depend on `ProgramIncrementIdentifierBuilder` to build `ProgramIncrementIdentifier`.

#### Value-object interface

A `value-object` interface always has a matching `Proxy` implementation. It is a way to circumvent the rule that forbids `Domain` classes from depending on other plugins or Tuleap Core classes (see [ADR-0002 Hexagonal Architecture][1]).

For example, we want to build a `UserIdentifier` from a `\PFUser` (to provide guarantees that `UserIdentifier` is valid, see [ADR-0003 Static factory method pattern][2]). Since `\PFUser` is a class from Tuleap core, we cannot write a class like `UserIdentifier` in the Domain, as this would break the rule. To work around this, we have a value-object interface `UserIdentifier` in the Domain, and an implementation of this interface `UserProxy` in the Adapters namespace. Since the implementation is in Adapters, it can be built from `\PFUser`. Domain classes never use the Proxy directly, but depend on the value-object interface, which is also in the Domain.

It can also be used to map Events from other plugins.

Value-object interfaces are always in the `Tuleap\ProgramManagement\Domain` namespace.

#### Verify`<something>`

Interfaces named `Verify` have one method that returns a `boolean`. They are meant to run a check (in database usually), like `VerifyIsProgram`.

## Links

- [SAFe glossary][0].
- [ADR-0002 Hexagonal Architecture][1]
- [ADR-0003 Static factory method pattern][2]

[0]: https://www.scaledagileframework.com/glossary/
[1]: <./decisions/0002-hexagonal-architecture.md>
[2]: <./decisions/0003-static-factory-method.md>
