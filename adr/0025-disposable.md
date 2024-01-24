# Disposable

* Status: accepted
* Deciders: Joris MASSON
* Date: 2024-01-23

Technical Story: [request #35866 Have an equivalent to Dispose pattern in PHP][0]

## Context and Problem Statement

When dealing with persistent resources (such as filesystem or databases), we often need to run some clean-up code. For example, in database integration tests, we create tables, fill them with data, and run our tests. However, if we forget to delete the data after our tests (passing or failing), it will stay in the database and might affect other tests. It can be easy to forget cleaning-up, especially when several tables are involved.

Can we find a way to _not_ forget cleaning-up ?

## Decision Outcome

The [Dispose pattern][6] could help us. Its goal is to make it impossible to forget cleaning-up by wrapping the usage of resources in a function and always calling the clean-up code at the end of that function.

There are two parts to it:
1. Classes holding a resource that needs to be cleaned-up or freed should implement the `Disposable` interface. The interface provides a `dispose()` method, clean-up code should be there. The method should be idempotent (can be called more than once).
2. Dependent classes using them should wrap what they do in `Dispose::using()`.

```php
// Instead of writing this:
class IntegrationTest
{
    protected function setUp(): void
    {
        $project_id   = $db->insertProject();
        $tracker_id   = $db->insertTracker();
        $changeset_id = $db->insertChangeset();
    }

    protected function tearDown(): void
    {
        $db->run('DELETE FROM groups');
        $db->run('DELETE FROM tracker');
        $db->run('DELETE FROM tracker_changeset')
    }

    public function testTracker(): void
    {
        // Run our test on project, tracker and changeset
    }
}
```

```php
// We can write this:
use \Tuleap\Disposable\Disposable;
use \Tuleap\Disposable\Dispose;

class IntegrationTestData implements Disposable
{
    public readonly int $project_id;
    public readonly int $tracker_id;
    public readonly int $changeset_id;

    public function __construct(private readonly \ParagonIE\EasyDB\EasyDB $db)
    {
        $this->project_id   = $this->db->insertProject();
        $this->tracker_id   = $this->db->insertTracker();
        $this->changeset_id = $this->db->insertChangeset();
    }

    public function dispose() : void
    {
        $this->db->delete('groups', ['group_id' => $this->project_id]);
        $this->db->delete('tracker', ['id' => $this->tracker_id]);
        $this->db->delete('tracker_changeset', ['id' => $this->changeset_id]);
    }
}

class IntegrationTest
{
    public function testTracker()
    {
        Dispose::using(new IntegrationTestData($db), function (IntegrationTestData $data) {
            // Run our test on project, tracker and changeset
        });
        // At the end, dispose() is called and data is cleaned-up
    }
}
```

[TypeScript 5.2 introduced this pattern][4] at the language level, so there is a built-in `Disposable` interface and a built-in `using` keyword.

### Recommendations and rules

Code that needs to clean-up after itself should implement the `Disposable` interface and write the clean-up in the `dispose()` method. Dependent code should always call `Dispose::using()` to access the `Disposable`.

### Positive Consequences

* `Dispose::using()` guarantees that the clean-up code will be run, even if there is an exception or a PHP Error.
* We cannot forget to clean-up anymore, but we should remember that code that needs to clean-up after itself should implement the `Disposable` interface.

### Negative Consequences

* None identified so far.

## Considered Options

* Pull `Disposable` from external libraries
* Write an implementation ourselves

Chosen option: "Write an implementation ourselves", because it comes out best in the comparison (see below).

## Pros and Cons of the Options

### Pull `Disposable` from external libraries

We could pull an existing implementation from [cgTag/php-disposable][5] library.

* Good, because it's slightly less initial work as we don't have to write code.
* Bad, because the last release was in 2017. The library probably does not need much maintenance, but still, a lot of things happened in 7 years, at the very least new PHP versions that are probably not tested.
* Bad, because it comes with the risk of future breaking changes that we will be forced to adapt to.

### Write an implementation ourselves

We could write a PHP implementation ourselves, as we have already done for [Result][1] and [Option][2]. It looks a lot simpler, as it is not an "either / or" case.

* Good, because it's not very complicated code.
* Good, because if we need to do breaking changes, we can adjust all dependent code in the codebase at the same time.
* Bad, because it's slightly more initial work.

## Links

* [Dispose pattern][6] on Wikipedia
* [PHP implementation][3]
* [TypeScript 5.2 language feature][4]

[0]: https://tuleap.net/plugins/tracker/?aid=35866
[1]: ./0013-neverthrow.md
[2]: ./0022-option.md
[3]: ../src/common/Disposable/README.md
[4]: https://www.typescriptlang.org/docs/handbook/release-notes/typescript-5-2.html#using-declarations-and-explicit-resource-management
[5]: https://github.com/cgTag/php-disposable/
[6]: https://en.wikipedia.org/wiki/Dispose_pattern
