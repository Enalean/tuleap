# Favor PHPUnit mock system over Mockery

* Status: accepted
* Deciders: Thomas GERBET, Joris MASSON, Manuel VACELET
* Date: 2021-04-30

Technical Story: [request #20917 Favor PHPUnit mock system over Mockery][0]

## Context and Problem Statement

Mockery was introduced to the Tuleap codebase to help us migrate away from SimpleTest to PHPUnit.
[This migration is now completed][1] so we have two competing mock systems in the codebase: Mockery and the PHPUnit
native one. Recently, the maintenance of Mockery has generated us more work than directly using the PHPUnit mock system
(e.g. [mockery/mockery #1106][2], [psalm/psalm-plugin-mockery #17][3]).

## Considered Options

* Start using PHPUnit mock system instead of Mockery
* Keep using exclusively Mockery

## Decision Outcome

Chosen option: "Start using PHPUnit mock system instead of Mockery", because this is the option that seems the less
likely to cause us issues in the long run.

## Pros and Cons of the Options

### Start using PHPUnit mock system instead of Mockery

* Good, because it will ultimately allow us to have one less dependency.
* Good, because we can benefit from the large PHPUnit community (support, tooling…).
* Good, because moving to a new PHP version will be easier thanks to [PHPUnit supported versions][4].
* Good, because new Tuleap developers are more likely to be familiar with it.
* Bad, because it forces Tuleap developers to become familiar with another mock system. However, since PHPUnit is
  frequently used within the community it is not rare to encounter PHPUnit's mocks in libraries Tuleap is using and so
  to be used to them.
* Bad, because we will have two mock systems in use until all the tests are eventually converted.

### Keep using exclusively Mockery

* Good, because it is already used massively in the codebase.
* Bad, because it forces us to put more work in the maintenance of our tests.


[0]: https://tuleap.net/plugins/tracker/?aid=21326
[1]: https://tuleap.net/plugins/tracker/?aid=14150
[2]: https://github.com/mockery/mockery/pull/1106/files
[3]: https://github.com/psalm/psalm-plugin-mockery/issues/17
[4]: https://phpunit.de/supported-versions.html
