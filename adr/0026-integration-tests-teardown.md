# Avoiding shared data for database integration tests

* Status: accepted
* Deciders: Marie Ange GARNIER, Thomas GERBET, Joris MASSON
* Date: 2024-01-24

Technical Story: [request #35868 Improve clean up of db integration tests][0]

## Context and Problem Statement

Tests for the integration between backend code and the database are a great tool that lets us validate that our SQL queries are correct and work as we expect them to. However, they have a key difference with "usual" Unit tests: they all share a single database. This means that any test data that is inserted in the database must be removed after the test is done, otherwise it could "contaminate" other tests and could influence their results.

This was already partially mitigated by always running the tests in a random order. It prevents tests from implicitly relying on previous data, but problematic behaviours can become "flaky" and appear only when the tests are ordered in a certain way. Regardless of the run order, we should always delete the data we inserted. However, it can be easy to forget cleaning-up, especially when several tables are involved.

Additionally, DB integration tests also import a couple of projects and trackers as fixtures during their setup, so we must make sure that we do not delete every project, tracker and user, otherwise some tests that depend on those fixtures will fail.

Can we find a good way to clean up DB test data ?

## Considered Options

* Clean-up manually
* Using the [Dispose pattern][1]
* Starting a transaction and rolling it back after the test

## Decision Outcome

Chosen option: "Starting a transaction and rolling it back after the test", because it comes out best in the comparison (see below). Other options will have to be used for edge cases, however.

### Positive Consequences

* DB integration tests should be easier to write and maintain. Particularly, it should be easier to write tests that can be run repeatedly, without breaking.

### Negative Consequences

* None identified so far.

## Pros and Cons of the Options

### Clean-up manually

Clean-up is done either by a `tearDown()` method or by a `tearDownAfterClass()` static method (or both). For most tables, we delete everything, but we cannot do that for projects (`groups`), `tracker` and `user` tables. Since those tables can contain imported setup fixtures, tests involving them must remember the IDs they created and only delete rows matching those IDs.

```php
final class IntegrationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private static int $not_milestone_tracker_id;

    // setUp() …

    protected function tearDown(): void
    {
        $db->run('DELETE FROM plugin_agiledashboard_planning');
        $db->run('DELETE FROM plugin_agiledashboard_planning_backlog_tracker');
    }

    public static function tearDownAfterClass(): void
    {
        $db->delete('tracker', ['id' => self::$not_milestone_tracker_id]);
    }
}
```

* Good, because it keeps the test structure we are used to, with set-up and tear-down phases.
* Bad, because it is easy to forget cleaning-up altogether.
* Bad, because when the set-up data is complicated (everything involving trackers, changesets and changeset values), the clean-up is also complicated with lots of tables to delete.
* Bad, because it is easy to forget about test fixtures and write a query that will delete every project. Since the tests are run in random order, there might be a delay before we realize that the tests depending on the fixtures are broken.

### Using the [Dispose pattern][1]

See [ADR-0025][1] for details on the Dispose pattern. We could wrap test set-up in a dedicated class implementing `Disposable`. We could insert data in the database in a "constructor" method, and we would be writing the same SQL delete queries as the "Clean-up manually" option, but in the `dispose()` method of the `Disposable` class.

* Good, because it is harder to forget cleaning-up altogether.
* Bad, because it would be hard to keep a familiar set-up, test, tear-down structure. Test set-up would be moved to one or several `Disposable` classes, in separate files. Since the Disposable can only be used in its callback function, we would have to wrap the test case in it.
* Bad, because when the set-up data is complicated (everything involving trackers, changesets and changeset values), the clean-up is also complicated with lots of tables to delete.
* Bad, because it is easy to forget about test fixtures and write a query that will delete every project. Since the tests are run in random order, there might be a delay before we realize that the tests depending on the fixtures are broken.

### Starting a transaction and rolling it back after the test

Instead of really inserting and really deleting data in the database, we could start an SQL transaction at the beginning of each test, and issue a rollback at the end. While inside the transaction, all `SELECT`s should keep working. After the rollback, no data has been inserted, so we no longer need to clean-up everything anymore. It might also be faster from a performance point of view.

However, it brings some new issues. If the code under test commits and starts a new transaction, data will really be inserted in the database (and not cleaned up, like we would expect). We can protect ourselves against this situation by leveraging `SAVEPOINT`. We can create a `SAVEPOINT` with a random ID, and roll back to it. Since save points are destroyed once a transaction is committed or rolled back, the database will raise an error, and we will be warned.

Some statements are "Data Definition Language" statements (like "TRUNCATE TABLE", see [MySQL Manual][2]). Such statements will implicitly commit ongoing transactions and cannot be rolled back. We will have to use another option to clean-up for such cases.

To ease the handling of the transaction, we can create a dedicated `TestCase` abstract class that will automatically start the transaction in a method marked with `@before` and issue a rollback in a method marked with `@after`.

```php
final class IntegrationTest extends \Tuleap\Test\PHPUnit\TestIntegrationTestCase
{
    // No need for tearDown() anymore
}
```

The `TestCase` can also clear singleton instances, and restore `ForgeConfig` in `@after` phase.

* Good, because it keeps the test structure we are used to, with a set-up phase. Most of the time, the tear-down phase is not needed anymore.
* Good, because clean-up is done "automagically", so we don't have to remember cleaning-up.
* Good, because it works even when the set-up data is complicated with lots of tables.
* Good, because it does not threaten test fixtures data. What was already written in the database stays untouched.
* Good, because having a separate `TestCase` class makes it easier to distinguish DB integration tests from unit tests. It makes them easier to search for.
* Good, because when old production code uses singleton instances, we won't forget to clear them. We will avoid tests that are flaky due to existing cache in singleton instances (for example there is a cache in `ProjectManager::instance()`, it cost us a lot of time to figure out that we had to clear its instance).
* Bad, because when data has been inserted in the database outside of `setUp()` or a test case, (for example in `setUpBeforeClass()`), it is not part of the transaction. We will have to use another option to clean-up for such cases.

## Links

* [ADR-0025: Disposable][1]
* [TRUNCATE TABLE statement][2] in MySQL Manual

[0]: https://tuleap.net/plugins/tracker/?aid=35868
[1]: ./0025-disposable.md
[2]: https://dev.mysql.com/doc/refman/8.0/en/truncate-table.html
