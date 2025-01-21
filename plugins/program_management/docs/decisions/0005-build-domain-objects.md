---
status: accepted, supersedes ADR-0003 Static factory method
date: 2024-12-05
decision-makers: Joris MASSON
consulted: Clarisse DESCHAMPS, Clarck ROBINSON
informed: Kevin TRAINI, Manuel VACELET, Marie Ange GARNIER, Nicolas TERRAY, Thomas GERBET, Thomas GORKA
---

# Build Domain objects without passing interfaces

## Context and Problem Statement

[request #41101][0] Build Domain objects without passing interfaces

In [ADR-0003 Static factory method][1], we tried to have stronger guarantees that our Domain data was valid. But "valid" can have a wide range of meanings. It is easy to do checks like "it must be a positive integer" for domain data like identifiers, and we can even enforce those checks with static analysis. However, some checks have to be done in "right adapters" (typically in Database), for example verifying whether a User can see an Artifact. With the Static factory method pattern, we are required to do those checks in the domain, and to pass interfaces as parameters of the static method "constructors". Shouldn't we instead make those checks in the right adapters and have the adapters build the correct domain objects from primitives ?

## Considered Options

* Status quo: Keep enforcing the usage of interface parameters
* Allow building Domain objects directly by Adapters

## Decision Outcome

Chosen option: "Allow building Domain objects directly by Adapters" because it comes out best in the comparison (see below).

### Confirmation

All domain objects (`ProgramIdentifier`, `TeamIdentifier`, `UserReference`, etc.) have simple constructors (or static methods) that take primitive values and no domain interfaces. The adapters (REST, Database, etc.) are responsible for building them with the correct checks depending on the context.

## Pros and Cons of the Options

### Status quo: Keep enforcing the usage of interface parameters

Usage of the static factory methods in lieu of constructors is not called into question. We should keep using private constructors and static methods to allow for "named constructors".

* Bad, the guarantee of validity can be broken too easily by adding constructors that do not run checks.
* Bad, it is too rigid and this forces us to introduce several objects to represent the same concept but with different validation checks (for example `ProgramIdentifier` and `ProgramForAdministrationIdentifier`).
* Bad, it forces us to have interfaces for database adapters that return arrays of primitives, and the static factory method _must_ know the format of this array, which is often tied to the DB structure. This makes the DB structure "leak" indirectly into the domain.
* Bad, because when we try to avoid the previous drawback, it leads to a proliferation of classes: [Value-object interface][2] + [somethingProxy][2] + sometimes "pure domain" classes (for example `DomainChangeset`).
* Bad, as in the author's experience, it takes a lot of focus, discipline and mental energy to figure out how to "do things right". It's hard to design interfaces at the right granularity level, and it's hard to know what they are supposed to return. This solution was supposed to reduce cognitive load, and while it might be true when _using_ domain objects, it looks like it has the opposite effect when designing them (especially with Value-object + Proxy).
* Bad, understanding and navigating the code is more difficult for newcomers, because there are a lot of classes. This point can be even worse when a class is not at the right place (for example a domain object implemented in adapter namespace).

#### Validity guarantee is easy to break

The original objective of the pattern was to force certain validity checks to be run, or put another way to make it impossible to build an object without running the checks. This was achieved by reversing the dependencies: the domain object builds itself, and calls the interfaces to make the checks it needs.

But in reality, it's quite easy to break the guarantee that the checks are made: just add another "constructor" that _does not run_ the checks. The pattern works if there is only one "constructor" method, but there are situations when some checks _must not be done_.

#### "Valid" depends on the context

For example, when running a Tracker Workflow Post-Action to add a Feature to the Top backlog, the current user will be a special system user "Tracker Workflow User". In this case, we _must not check_ whether the user has permission to access the Program project, or whether they are allowed to see the Feature, because since it is a system user, it is not added explicitly to allowed user groups in the permission system (it is implicitly considered as having permission). If we really run the checks, they will fail, and it will fail the task. This led to the invention of a `PermissionBypass` object that was passed in a lot of methods and whose goal was to disable the permission checks.

Another example where checks depend on the context: checking validity of a Program. When we designed `ProgramIdentifier`, one of the goals was to ensure that the Program project is a valid project, that the current user can access it, and that it has Team projects linked to it. However later, when we designed the administration pages for Program Management, the constraint was to deal with Programs that are _not yet_ linked to any teams, and the current user must be project administrator of the underlying project. In order to maintain the guarantees of `ProgramIdentifier`, we built another object `ProgramForAdministrationIdentifier` that does the second set of checks. Later still, as we worked on inheritance of configuration when creating a Program from a Template project, we agreed that a "Program" in this context did not need to have any teams linked, and that it was a "Program" if the "Program" service was turned on in the project. If we did the checks on Team projects in the context of Template projects they would always fail, because a Template project is never linked to teams, but its configuration must be inherited regardless.

The previous stories illustrate that "validity" checks (or permission checks) depend on the situation, and the majority of those checks are made by right adapters. The domain should not have to care about Project Services, or Tracker Workflow users, or Project administrators. Yet, those concepts can leak into the domain, because right adapters are not allowed to directly build domain objects. It is the reverse: the domain objects use right adapters to build themselves.

#### Leaky abstraction

This reversal can also leak Database structure into the domain. See the following (simplified) example:

```php
// This is an interface in front of a Database adapter
interface RetrieveProgramIncrementLabels
{
    // Here, the database table structure is leaked to the domain
    /**
    * @psalm-return array{program_increment_label: ?string, program_increment_sub_label: ?string}
    */
    public function getProgramIncrementLabels(int $program_increment_tracker_id): array;
}

class ProgramIncrementLabels
{
    private function __construct(
      public ?string $label,
      public ?string $sub_label,
    ) {
    }

    public static function fromProgramIncrementTracker(
      RetrieveProgramIncrementLabels $label_retriever,
      TrackerReference $tracker,
    ): self {
      $labels = $label_retriever->getProgramIncrementLabels($tracker->getId());
      // This object, while belonging to the domain, knows about the
      // database table structure through the array. It would be better
      // if the interface returned an object, but in this case wouldn't
      // it be redundant? The object would be exactly the same as this
      // class. It's a bit of a chicken-egg problem…
      return new self($labels['program_increment_label'], $labels['program_increment_sub_label']);
    }
}
```

In order to avoid those leaks, we had a solution: the domain object becomes an interface (which means no properties, so a lot of getters). We build a corresponding class in the adapters. We called this couple [Value-object interface][2] + [`<something>`Proxy][2]. But when we need to build such a Value Object from the domain itself, we have to write another "pure domain" class (for example `DomainChangeset`)… And given that many Domain objects must be built by adapters (either Database adapters, REST adapters, Trackers, Project, User, etc…), this leads to a profusion of classes and interfaces. The domain becomes almost exclusively interfaces, but the goal of having a domain was also to represent data and operations on it…

### Allow building Domain objects directly by Adapters

Instead of requiring that domain objects build themselves, they have simple constructors or static methods that take primitive data. It is the adapters that are responsible for making the checks and building them with valid data.

* Bad, because it relies on adapters for the validity of domain objects.
* Good, because it is more flexible and allows us to build the same object with checks that depend on the context.
* Good, because interfaces can return domain objects, they don't leak the database table structure.
* Good, because it reduces the number of classes: domain objects are a single class, instead of an interface + an adapter implementation + a domain implementation.
* Good, because it is significantly easier from a cognitive load standpoint. We are used to "Factory" classes that create domain objects, here we can have "Factory" right adapters that "just" return domain objects.

Example:

```php
// Domain
interface RetrieveProgramIncrementLabels
{
    public function getProgramIncrementLabels(TrackerReference $tracker): ProgramIncrementLabels;
}

// Domain
class ProgramIncrementLabels
{
    public function __construct(public ?string $label, public ?string $sub_label)
    {
    }
}

// DB Adapter
class LabelsDAO implements RetrieveProgramIncrementLabels
{
    public function getProgramIncrementLabels(TrackerReference $tracker): ProgramIncrementLabels
    {
        // DB query → $row
        // The database structure stays inside the DB adapter
        // Concepts and verifications from the adapter do not leak into the domain
        return new ProgramIncrementLabels($row['program_increment_label'], $row['program_increment_sub_label']);
    }
}
```

## More Information

* [ADR-0003 Static factory method][1]

[0]: https://tuleap.net/plugins/tracker/?aid=41101
[1]: 0003-static-factory-method.md
[2]: ../glossary.md
