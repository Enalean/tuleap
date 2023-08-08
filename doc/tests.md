# Tests & automated inspections

## Tests

All contributions are expected to come with tests. We strongly encourage developers to apply Test Driven Development (TDD) principles.

We have four level of testing:
* Unit tests
* Integration tests
* Automated end-to-end tests
* Manual end-to-end tests

It's expected to have as much as possible of the former and at least as possible of the latter.

A typical feature development comes in several patches.
* Each fine grain patch is expected to come with associated unit tests
* When feature is mature enough, it should come with either an Integration, End to End or Manual test.

### Unit tests

Unit tests is the finer grain level of tests. They are not meant to test one class/file/method. On the contrary we encourage
to write tests about the behaviour that is wanted rather than the implementation.

A unit test doesn't have external dependencies (network call, database, web ui) but it can have dependency on
the file system (reading/writing files).

Unit tests must be independent of each others and must not have side effects.

More details on how to write and run [unit tests](./tests/unit-test.md).

Unit tests corresponds to steps "UT PHPUnit" and "JS Unit tests" in [development continuous integration pipeline](https://ci.tuleap.org/jenkins/job/tuleap-gerrit-tests/)

### Integration tests

Integration tests corresponds to code tests (hence use of unit test framework) with dependency on external elements
(database, system tools like git, etc).

They rely on a real Tuleap stack. The data set is 100% under control (XML import of sample data at each run).

There are two types of integration tests:
* SQL integration tests (aka DB tests)

    The goal is to test the SQL queries as well as their integration with surrounding code.
    Those tests are expected when dealing with complex SQL queries.

* REST API tests

    Tests are their to ensure correctness of the API calls as well as main continuous verification of backward compatibility
    of the public API.

More details on how to write and run [integration tests](./tests/integration.md).

### Automated end-to-end tests

Automated end-to-end tests corresponds to real case scenario. They rely on a real Tuleap stack and tests are
executed in a browser. The data set is 100% under control (XML import of sample data at each run).

They are slow, hard to write and tend to be more brittle due to the high number of moving parts involved.

Yet, they are still valuable to cover real use case and ensuring that the full system works as expected.
In some situations, for instance Legacy Code, they are easier to write to have a minimal level of confidence
than writing unit or integration tests on a code not designed for tests.

More details on how to write and run [end-to-end tests](./tests/end-to-end.md)

### Manual end-to-end tests

Some tests cannot be automated with our current technology stack or they are manual tests for historical reasons
(ie. they were there before any automation framework).

Those tests are high level scenario and should only cover happy path or critical error cases that cannot be
tested with other means.

Manual end-to-end tests are managed with Tuleap Test Management tool and stored on tuleap.net platform.

New tests must be linked to the User Story they correspond to.

The [Validation Campaigns](https://tuleap.net/plugins/testmanagement/?group_id=101#!/campaigns) are run prior each major release.
Test Campaigns and results are not public.

### Resources

* [Test pyramid](https://martinfowler.com/articles/practical-test-pyramid.html#TheTestPyramid)
* [Outside in Diamond TDD part 1](https://tpierrain.blogspot.com/2021/03/outside-in-diamond-tdd-1-style-made.html)
* [Outside in Diamond TDD part 2](https://tpierrain.blogspot.com/2021/03/outside-in-diamond-tdd-2-anatomy-of.html)

## Automated inspections

In addition to tests, there are automated inspection of the code for correctness:
* Coding standard
  * PHP coding standard
  * ESLint
  * SCSS coding standard
* Static analysis
  * Psalm
  * ESLint
  * Taint analysis
  * Dependencies vulnerabilities scanner
* Architecture analysis
  * Deptrac
* Build & run

### PHP coding standard, ESLint & SCSS coding standard

Ensure submitted code follows defined coding rules.

See [dedicated section](./coding-standards.md).

### Static analysis of PHP code with Psalm

Static analysis for PHP. Ensure PHP code correctness beyond what is done by regular PHP Parser
with stricter rules, especially regarding types.

Due to Tuleap age, it's not possible to enforce rules everywhere. There is a baseline of errors that should
lead to errors but are accepted. Psalm vetting is only mandatory for new code.

See [Psalm web site](https://psalm.dev/).

### Static analysis of Javascript and Typescript code with ESLint

Some basic static analysis is performed with ESLint checks in addition to coding standard. See https://github.com/mozilla/eslint-plugin-no-unsanitized

In addition to that, front end development rules requires that [new code must be written in Typescript](./front-end/javascript.md). A large part
of static analysis is implied by usage of Typescript and build of Javascript/Typescript apps.

### Deptrac

Deptrac allows to define rules to ensure code organisation/structure/architecture keep following defined
patterns over the time.

For instance, this helps to avoid leaks between parts in modules that follow [hexagonal architecture](https://en.wikipedia.org/wiki/Hexagonal_architecture_(software)).

See [Deptrac web site](https://qossmic.github.io/deptrac/).

### Build & run

This step will build Tuleap RPMs (as for final delivery), install them on a fresh container and run a basic
hello world test.

This ensures that packaging and installation process is not broken.

### Taint analysis

Taint analysis attempt to keep trac of user inputs throughout the execution flow. The goal is to identify places
where such inputs are not properly escaped before going to another system like a database. In that case it
helps to prevent SQL injections.

See [psalm section on taint analysis](https://psalm.dev/docs/security_analysis/).

### Dependencies vulnerabilities scanner

Dependencies vulnerabilities scanner uses [OSV Scanner](https://google.github.io/osv-scanner/) to identify
dependencies (php, javascript, rust, go) that are affected by a published vulnerability.

## What is run and when (aka Continuous Integration rules)

All automations are centralized on https://ci.tuleap.org/jenkins/. The jobs that matters regarding previous content:
- [Gerrit tests](https://ci.tuleap.org/jenkins/job/tuleap-gerrit-tests/): run on every patcheset submitted on gerrit. Everything must pass prior review and integration.
- [Master tests](https://ci.tuleap.org/jenkins/job/tuleap-master-tests/): run after each merge on master. Everything must pass prior to generating packages & docker images.
- [Nightly tests](https://ci.tuleap.org/jenkins/job/TuleapNightlyTests/): run each night, not blocking but team must act on a timely manner (step of daily review).
- [Taint analysis](https://ci.tuleap.org/jenkins/job/tuleap-security-taint-analysis/): run each night, not blocking but team must act on a timely manner (step of daily review).
- [Scan vuln deps](https://ci.tuleap.org/jenkins/job/tuleap-security-scan-vuln-deps/): run each night, not blocking but team must act on a timely manner (step of daily review).
- [Monthly Validation](https://ci.tuleap.org/jenkins/job/TuleapMonthlyValidation/): run each month. Blocking for monthly release.

| | Gerrit tests | Master tests | Nightly tests | Taint analysis | Scan vuln deps | Monthly Validation |
|-| ------------ | ------------ | ------------- | -------------- | -------------- | ------------------ |
| Unit tests | x | x | x |  |  |  |
| Integration tests | x | x | x |  |  |  |
| Automated e2e tests |  |  | x |  |  | x |
| Manual e2e tests |  |  |  |  |  | x |
| Coding standards | x | x |  |  |  |  |
| Psalm | x | x |  |  |  |  |
| Deptrac | x | x |  |  |  |  |
| Build & run | x | x | x |  |  |  |
| Taint analysis |  |  |  | x |  |  |
| Dependencies vulnerabilities scanner |  |  |  |  | x |  |
