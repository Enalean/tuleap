# Writing unit-tests without mocks

* Status: accepted
* Deciders: Joris MASSON, Marie Ange GARNIER
* Date: 2021-11-15

Technical Story: [epic #16683 Program Management][0]

## Context and Problem Statement

When writing unit-tests, we usually make extensive use of Mocks such as [Mockery][1] or more recently PHPUnit's own [Mock System][2]. It's a habit that had almost become a reflex in our team. Mocks have several downsides though. If you forget to tell the mock about a method call, it will complain. This leads to copying and pasting mock definitions across tests in the same Test class. We also repeat their definition in every Test class, for example every test class that depends on `\Tracker_ArtifactFactory` will rewrite tailored mocks for it. This repetition also reduces readability of our tests. When 80% of your test method is mock definitions that are copy-pasted _except for one small detail_, it becomes hard to spot what is different between two test methods. Mocks are also tightly coupled to method names. If you want to rename a method, your IDE won't help you rename all the mocks depending on it. You will have to search them and rename them manually. Sometimes Mocks are also subtly incorrect, for example they return an int when the production code returns a string.

How can we write unit tests that rely less on mocks ? Can we write unit tests that are _mock-free_ ?

## Decision Outcome

Following [ADR-0002 Hexagonal Architecture][3], we now make extensive use of Interfaces between code from the Domain and code from the Adapters. We can provide fake implementations of those interfaces for tests. At first, we wrote anonymous classes, but since we reuse those interfaces over and over, we created `Stubs` (see our [glossary][4]). Stubs are a kind of Adapter used only for tests. They implement the interfaces in a really basic way and can be prepared to always return the same value. They can be easily reused across Test methods and classes.

Using Stubs allows us to stop mocking DAOs and Factories. We create the right stub for the situation (for example, a Stub that always returns true), and we inject it in our class under test. This lets us create _Overlapping Sociable Tests_ (see [James Shore's blog post][6]) that are kind of Integration tests. We can still use Mocks in Adapter unit tests, but in all other cases Stubs must be preferred.

For example, a Stub for a boolean method:
```php
// Stubs are always in the Tests\Stub namespace
namespace Tuleap\ProgramManagement\Tests\Stub;

// Stub class name is always "Interface name" followed by "Stub"
// Stubs classes are always "final"
final class VerifyIsIterationStub implements VerifyIsIteration
{
    // Constructor is private, it follows the static factory method pattern
    private function __construct(private bool $is_valid)
    {
    }

    public function isIteration(int $artifact_id): bool
    {
        return $this->is_valid;
    }

    // Static factory methods names for Stubs usually start by "with".
    // It builds a stub "with" a return value that is always the same.
    public static function withValidIteration(): self
    {
        return new self(true);
    }

    // For "negative" cases, we usually name them "withNot".
    // "withInvalidIteration" is visually too close to "withValidIteration".
    public static function withNotIteration(): self
    {
        return new self(false);
    }
}
```

A Test Case using Stubs:

```php
final class SomeClassUsingOurStubTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private VerifyIsIterationStub $iteration_verifier;
    private RetrieveIterationStub $iteration_retriever;

    protected function setUp(): void
    {
        // Define properties with valid stubs by default
        $this->iteration_verifier  = VerifyIsIterationStub::withValidIteration();
        $this->iteration_retriever = RetrieveIterationStub::withIterationId(125);
    }

    private function doStuff(): boolean
    {
        $user          = UserIdentifierStub::buildGenericUser();
        $stubbed_class = new SomeClassUsingOurStub(
            $this->iteration_verifier,
            $this->iteration_retriever,
        );
        return $stubbed_class->doStuff($user);
    }

    public function testValid(): void
    {
        // Stubs are valid by default, there is nothing else to define
        self::assertTrue($this->doStuff());
    }

    public function testInvalidIteration(): void
    {
        // Override the property with a stub that returns false
        $this->iteration_verifier = VerifyIsIterationStub::withNotIteration()
        self::assertFalse($this->doStuff());
    }

    public function testNoIteration(): void
    {
        // Only the stub that matches the test scenario changes from default values.
        // We don't copy and paste the same mock definitions over and over again.
        $this->iteration_retriever = RetrieveIterationStub::withError();
        self::assertFalse($this->doStuff());
    }
}
```

A Stub for a `void` method (with side effects only):
```php
namespace Tuleap\ProgramManagement\Tests\Stub;

final class AddArtifactLinkChangesetStub implements AddArtifactLinkChangeset
{
    private int $call_count = 0;

    public static function withCount(): self
    {
        return new self();
    }

    public function getCallCount(): int
    {
        return $this->call_count;
    }

    public function addArtifactLinkChangeset(ArtifactLinkChangeset $changeset): void
    {
        $this->call_count++;
    }
}
```

Use the `getCallCount()` method to assert the stub has been called or not:
```php
final class SomeClassUsingOurStubTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private AddArtifactLinkChangesetStub $changeset_adder;

    protected function setUp(): void
    {
        $this->changeset_adder = AddArtifactLinkChangesetStub::withCount();
    }

    private function doStuff(): void
    {
        $iteration     = IterationIdentifierBuilder::buildWithId(98);
        $stubbed_class = new SomeOtherClassUsingOurStub($this->changeset_adder);
        $stubbed_class->doStuff($iteration);
    }

    public function testHasBeenCalled(): void
    {
        $this->doStuff();
        self::assertSame(1, $this->changeset_adder->getCallCount());
    }

    public function testHasNeverBeenCalled(): void
    {
        $this->doStuff();
        self::assertSame(0, $this->changeset_adder->getCallCount());
    }
}
```

### Recommendations and rules

* Stubs are always in the `Tests\Stub` namespace.
* Stubs are always named `<InterfaceName>Stub`.
* Stubs classes are always `final`.
* Stubs should be kept as simple as possible.
* Only use Mocks when writing tests for Adapters. In all other cases, Stubs must be preferred.

### Positive Consequences

* It greatly encourages reuse of Stubs as opposed to copying and pasting mock definitions.
* It improves readability of test methods. We can set up default stubs for the most common cases in `setUp()` and replace only one stub to create our test's conditions. We no longer drown in copy-pasted mock definitions where only one detail changes from the previous test.
* Tests are much less tied to implementation: we have been surprised more than once by making a refactoring, running the tests and seeing them pass without having to change anything but the Stub.
* [Static factory method][5] names improve readability. We can guess the return value of the stub by reading the method name.
* Renaming our interfaces' methods is much easier: we only need to change one Stub instead of hundreds of Mocks. Our IDE can even find and rename it for us.
* We can write _Overlapping Sociable Tests_.

### Negative Consequences

* Test classes are no longer self-sufficient. To truly review a Test class, one must also review the related Stubs' code.
* Some Adapters depend on external plugins' code that does not use interfaces. In those cases only, we must still use Mocks to write unit-tests.
* When the Stub you need does not exist yet, it feels like more work to write the Stub class rather than writing a Mock.

## Links

* [ADR-0002 Hexagonal Architecture][3]
* [ADR-0003 Static Factory Method][5]
* [Glossary][4]

[0]: https://tuleap.net/plugins/tracker/?aid=16683
[1]: http://docs.mockery.io/en/latest/index.html
[2]: https://phpunit.readthedocs.io/en/9.5/test-doubles.html
[3]: 0002-hexagonal-architecture.md
[4]: <../glossary.md>
[5]: 0003-static-factory-method.md
[6]: https://www.jamesshore.com/v2/projects/nullables/testing-without-mocks
