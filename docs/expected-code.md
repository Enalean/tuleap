# Expected code

Tuleap is a big ([+750k
LOC](https://www.openhub.net/p/tuleap/analyses/latest/languages_summary))
and old (20 years) software and has probably an example of every
existing bad designs that existed during those 20 years.

Yet, it's not inevitable and we are on the way to slowly and carefully
clean things up. On our road toward a Clean Code, some motto might help
you to make your design choices:

-   Test your code; TDD (Test Driven Development) should be the default.
-   Follow
    [SOLID](https://en.wikipedia.org/wiki/SOLID_%28object-oriented_design%29)
    design principles.
-   Don't contribute
    [STUPID](https://www.npopov.com/2011/12/27/Dont-be-STUPID-GRASP-SOLID.html)
    code.

We also strongly suggest that you familiarize yourself with [Code
Smells](https://blog.codinghorror.com/code-smells/) as it might be used during code reviews.

## Secure coding practices

Contributors must follow [Tuleap Secure Coding Practices](./secure-coding-practices.md).

## Write code optimized for reading

All new contributors should be aware that people will spend way more
time reading their code than the time they will spend writing it.

From this fact, the rule of thumb is: **write code that is easy to read
rather than code that is easy to write**.

It's not really easy to know what a code "easy to read" is so here
are a few hints to help you:

-   Have a good unit test coverage of your code
    -   Have simple tests that can be read without having to switch
        between 3 files to understand what is the input and what is the
        output. Ideally a test should be readable without going out of
        the test method (+setUp).
    -   Use simple assertions (assertEquals, assertTrue, assertFalse
        should be enough most of the time).
    -   Be very strict about what you expect
-   Write smaller classes
-   Do not try to be "clever/smarter/subtle/\..." unless absolutely
    needed. Write dumb code.
    -   Make strong usage of types and static analysis of your code. If
        you cannot use your IDE to navigate easily in your code, odds
        are that you are trying to do something too smart.
    -   Be very careful with
        [over-engineering](https://en.wikipedia.org/wiki/Overengineering).
-   Do not introduce an abstraction if there is only one thing that
    needs to be abstracted.
-   Respect [YAGNI (You Ain't Gonna Need
    It)](https://www.martinfowler.com/bliki/Yagni.html) as much as
    possible. For instance, do not introduce something in a commit
    "because I will need it later" (pro-tip: you won't and the code
    will rot).

## Tuleap principles

As of June 2018, the general guidelines are:

-   Autoloader must be done with composer
-   Plugins should not expose a `www` directory anymore
    (exception for images)
-   New end points must be exposed via `FrontRouter`
-   Mostly static pages that are rendered server side using mustache
    templating (with some vanilla Javascript for simple interactions).
-   Rich, dynamic, pages that are rendered client side using Vuejs.
-   New usage of jQuery (or worst, prototypejs) should be avoided
-   Database code should use `EasyDB`
-   PHP tests should use `PHPUnit`

## Internationalization

Because Tuleap is used by a large community of users, it is
internationalized. For now, available languages are:

-   English
-   French

Thus, there shouldn't be any untranslated words or sentences of natural
language in source code. This applies to any strings displayed to end
users (web, emails). Logs or system messages are in english.

See [Internationalization](./internationalization.md) for
details.

## Commits

As a commit is reviewed individually, it must be "autonomous"
(corresponding to a task). It's a small part of a bigger story but
it's fully functional at its level.

Ideally, a commit is the smallest possible part of a feature. It's
perfectly fine to push "refactoring only" commits. It's actually a
bad practice to mix a refactoring of an existing code with a change of
behaviour.

A good commit:

-   Doesn't break existing behaviour (unless it's intended to, well
    documented and with an escape path for current users).
-   Has tests (automated: unit, REST or functional, described in
    commit message)
-   Has security guards (filter inputs, escape outputs, csrf tokens)
-   Has I18N code
-   Can be "not UI perfect" as long as there is a short term action
    (commit) to address it validated by the Design team
-   Might not have a direct effect on UI (modifications not visible) if
    it helps to reduce the size of upcoming reviews

A bad commit has:

-   Fatal errors, warnings, notices
-   Was not refactored
-   Cannot work without "the next one"
-   A meaningless commit message

As a contributor, it's your duty to get your commits integrated, it's
useless to stack-up commits that depend on one another if the very first
one is not validated.

# Tuleap coding standards

## PHP code formatting

As Tuleap is mainly written in PHP, we use the PSR standards. Standards are enforced by PHPCS in pre-commit hooks as
well as in CI.

## Javascript code formatting

Two tools parse JavaScript files (.js) and Vue files (.vue) on a
pre-commit hook, as well as in CI:

-   [eslint](https://eslint.org/) helps to check for errors, unused
    variables, strange syntax and other potentially problematic code.
    Some errors can be automatically fixed, but others cannot.
-   [prettier](https://prettier.io/) formats code to a universal,
    opinionated standard. It can format files automatically.

Those tools are not there to annoy you. We enforce these rules to
maintain consistency (as much as possible) in the very large and diverse
Tuleap codebase. Automated tools also help integrators speed up the
review process. Nobody wants to spend hours leaving comments about code
style, and nobody wants to spend hours fixing code to satisfy those
comments ;).

You should configure your editor or IDE to automatically report linter
errors. This will give you the fastest feedback. If some code does not
conform to formatting or syntax rules, the pre-commit hook will reject
it.

## Sass code formatting

Sass files (.scss) are also parsed by an automated tool on a pre-commit
hook, as well as in CI. We currently use [stylelint](https://stylelint.io/) to
automatically check Sass files.

This tool will warn you when you make a mistake in a Sass rule. It will
also enforce some stylistic conventions such as using shorthand
notations or ordering the properties in rules.

This time also, feel free to configure your editor or IDE to
automatically report linting errors. This will give you the fastest
feedback. The pre-commit hook will warn you otherwise.

## Internal conventions

-   Use an indent of 4 spaces, with no tabs. This helps to avoid
    problems with diffs, patches, git history...
-   It is recommended to keep lines at approximately 85-100 characters
    long for better code readability.
-   methodsInCamelCase()
-   $variables_in_snake_case
-   constants in UPPER_CASE
-   All added code should follow PSR-12. Existing code should be
    converted to PSR-12 in a dedicated commit in order to not clutter
    the review of your functional change.
-   No trailing whitespaces
-   In DataAccessObject, convention is to name `searchXxx()` the methods
    that returns a set of rows (eg. `searchProjectsUserIsAdmin(…)`, and
    `getXxx`, `isXxx`, `hasXxx` for other cases (eg.
    `doesUserHavePermission(…)`).

## What's in my contribution ?

Contributions SHOULD NOT add/fix features AND fix coding standard of a
legacy file in the same review. The code WON'T be accepted. If your eyes
are bleeding, conform to coding standard in a dedicated review, then
contribute your change.

This is especially true for refactoring, where the goal is to improve a
part of the code. Extracted crappy code to a dedicated file does not
need to be refactored, in order to ease the review (You may need to use
one of [ignore capabilities of
phpcs](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Advanced-Usage#ignoring-files-and-folders)
in order to pass coding standards check). Contributor has to focus his
mind on one task at a time.

Remember: refactoring is here to improve the existing code without
breaking functionality.

## Copyright & license

All source code files (php, js, bash, ...) must contain a page-level
docblock at the top of each file. This header includes your copyright
and a reference to the license GPLv2+.

``` php
/**
 * Copyright (c) Enalean, <Year> - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */
```

Adapt the copyright line to your situation.
