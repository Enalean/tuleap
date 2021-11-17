# Static factory method

* Status: accepted
* Deciders: Joris MASSON (@jmasson), Marie Ange GARNIER (@mgarnier)
* Date: 2021-10-12

Technical Story: [epic #16683 Program Management][0]

## Context and Problem Statement

We have taken the habit of [passing a bunch of primitive values][2] (such as `int`, `boolean`, `string`) to constructors for value objects, but it does not give strong guarantees. I could pass `-1` for an `int` parameter and it would be perfectly valid. I could also pass an empty string `''` for a `string` parameter. We have also taken the habit of passing invalid values as parameters in unit tests. For example, it is common to find `\Project` with id `100`, but `100` is a special value for project ids: it can only be the default template project. This habit increases the likelihood that we find such values allowed in production code. How can we get stronger guarantees that our objects are valid ? How can we make it harder (or impossible) to represent invalid objects in our code ?

## Decision Outcome

We have started to use the "static factory method" pattern.

The constructor is set to `private` and has the usual primitive or object parameters (`int $id`, `string $label`, `UserIdentifier $user`, and so on).

The object has a static method that returns `self`. Essentially, the static method becomes a sort of [named constructor][3]. It is also responsible for running all checks needed. For example, if you build a `ProgramIncrementIdentifier` from an `int`, you must verify that the parameter matches an actual `Artifact` from the Program Increment tracker (see our [glossary][1]), and that this Artifact is visible by your current user. To make these checks, your static method also receives interfaces (see [ADR-0002 Hexagonal Architecture][4]) that will make the verifications.

```php
final class ProgramIncrementIdentifier
{
    // Constructor is made private on purpose
    private function __construct(private int $id) {}

    /**
     * @throws ProgramIncrementNotFoundException
     */
    public static function fromId(
        // Interface parameters come first
        VerifyIsProgramIncrement $program_increment_verifier,
        VerifyIsVisibleArtifact $visibility_verifier,
        // Then, other parameters
        int $artifact_id,
        UserIdentifier $user
    ): self {
        if (
            ! $program_increment_verifier->isProgramIncrement($artifact_id)
            || ! $visibility_verifier->isVisible($artifact_id, $user)
        ) {
            // The method can throw exceptions when invalid data is passed
            throw new ProgramIncrementNotFoundException($artifact_id);
        }

        return new self($artifact_id);
    }

    public static function fromArtifactUpdated(
        // Interface parameters come first
        VerifyIsProgramIncrementTracker $program_increment_verifier,
        // Then, other parameters
        ArtifactUpdatedEvent $artifact_updated
    ): ?self {
        if (! $program_increment_verifier->isProgramIncrementTracker($artifact_updated->getTrackerId())) {
            // The method can also return null when invalid data is passed
            return null;
        }
        return new self($artifact_updated->getArtifactId());
    }
}
```

Since there is usually only one static method, it is now impossible to build the object _without the verifications_. Production code _cannot_ create a valid object without making the verifications. This gives us much stronger guarantees: whenever I use a `ProgramIncrementIdentifier` instead of an `int`, **I know** that it is valid and I can use it safely. There is no need to multiply the verifications. There is no way to forget making the verifications.

There is still a way to "cheat" this by passing stub/empty implementations of interfaces. Code review should not allow this misbehaviour in production code.

It is possible to have more than one static method. The other methods must take care to give the same level of guarantees and enforce verifications. For example, it is legit to create a `ProgramIncrementUpdate` from an Event and from the database. In this case, the methods are named `fromEvent()` and `fromStorage()` to distinguish them.

### Differences with the Builder pattern

We are not used to passing interfaces as method parameters, and it can feel awkward at first. Usually, we would inject those interfaces as constructor parameters. Here, since the method is `static`, the only way is to pass them as method parameters.

Instead of a static method, we could choose to make a second `Builder` object responsible for building our `ProgramIncrementIdentifier`. However, we would be forced to make `ProgramIncrementIdentifier`'s constructor public again. Since the constructor would be public, we could not guarantee that it is not called by some other class or with invalid parameters. We could not guarantee either that all checks are made before calling the constructor. We would be back to square one. Builders cannot provide the same guarantees as the static factory method pattern.

### Multiple returns

In some cases, you can even build multiple instances of your object. For example, it is expected that a `Program Increment` has multiple corresponding `Mirrored Program Increments`. A method to build `MirroredProgramIncrementIdentifier` from a `ProgramIncrementIdentifier` must return more than one result. In that case, the static factory method allows us to build an array of objects. A constructor would only let us build them one by one.

```php
final class MirroredProgramIncrementIdentifier
{
    private function __construct(private int $id)
    {
    }

    // Add a docblock to specify the type of objects in the array (always "self[]")
    /**
     * @return self[]
     */
    public static function buildCollectionFromProgramIncrement(
        // Interface parameters come first
        SearchMirroredTimeboxes $timebox_searcher,
        VerifyIsVisibleArtifact $visibility_verifier,
        // Then, other parameters
        ProgramIncrementIdentifier $program_increment,
        UserIdentifier $user
    ): array {
        $ids               = $timebox_searcher->searchMirroredTimeboxes($program_increment);
        $valid_identifiers = [];
        foreach ($ids as $id) {
            if ($visibility_verifier->isVisible($id, $user)) {
                // If none of the objects are visible, it will return an empty array.
                // The method could also throw an exception.
                $valid_identifiers[] = new self($id);
            }
        }
        return $valid_identifiers;
    }
}
```

### Naming conventions

By convention, the static factory method is named `from<Something>()` where `<Something>` is the main parameter (for example `fromId()`). Implicitly, it means `buildFrom<Something>()`, but since the method returns `self`, we can omit the `build` prefix, as it should be obvious from the signature that the method "builds" an instance.

It is named `from<Something>()` to be able to distinguish the kind of parameter it takes when there is more than one static factory method. Taking the habit of naming them always `from<Something>()` instead of `build()` will make it easier to add new methods, as we won't have to rename all the old ones.

It is conventional to always pass the interface parameters first and then the other (sometimes primitive, sometimes object) parameters.

By convention, we name static factory methods that return arrays `buildCollectionFrom<Something>()`. The `buildCollection` prefix makes it more obvious that we expect this method to return an array (that could be empty) instead of a single result.

For `Stubs` (see [ADR-0004 Writing unit-tests without mocks][5]), they also have static methods to build them. By convention, their static method is named `with<Something>()`. They are usually built to always return the same result, so the method "builds" a stub "with" a result. Again, since the method returns `self`, we can omit the `build` prefix as it should be obvious from the signature.

### Tests

Since it is now impossible to build an object without making the verifications, how do we write unit tests ? Are the tests going to call the database ?

Since the "verifications" parameters are always interfaces thanks to [Hexagonal Architecture][4], we can pass Stub implementations in tests. Stubs will fake the verifications and will not call the database. If you need a `ProgramIncrementIdentifier` in tests, you can build one like this:

```php
final class SomeTest
{
    public function someTest(): void
    {
        $program_increment_id = 12;
        $user                 = UserIdentifierStub::withId(101);

        $program_increment = ProgramIncrementIdentifier::fromId(
            VerifyIsProgramIncrementStub::withValidProgramIncrement(),
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            $program_increment_id,
            $user
        );
    }
}
```

We repeat instantiation of some of our classes so many times in Tests that we had to write `Test Builder` classes that will simplify building objects for tests. For example, there is a `ProgramIdentifierBuilder`. We also create Test Builders for objects that are quite complicated and require a lot of Stubs or many steps to build.

### Positive Consequences

* It provides strong guarantees that the objects we are working with are valid. We can reuse them across the stack and be assured that checks have been made when the object was created.
* It reduces cognitive load.
* It reduces [primitive obsession][2].

### Negative Consequences

* Objects that have many dependencies become harder to build. One could argue that it's actually a positive consequence, as it makes it harder to create invalid instances of complicated objects. It also makes us conscious of the many steps required to build such objects. `Test Builders` can help ease the pain of building complicated objects for tests.

## Links

* Program Management [epic][0]
* Definition of terms: [glossary][1]
* [ADR-0002 Hexagonal Architecture][4]
* DataClump (primitive obsession) [bliki][2]
* Named Constructors in PHP [blog post][3]
* [ADR-0004 Writing unit-tests without mocks][5]

[0]: https://tuleap.net/plugins/tracker/?aid=16683
[1]: <./glossary.md>
[2]: https://martinfowler.com/bliki/DataClump.html
[3]: https://verraes.net/2014/06/named-constructors-in-php/
[4]: <./0002-hexagonal-architecture.md>
[5]: <./0004-mock-free-tests.md>
