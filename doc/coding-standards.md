# Tuleap coding standards

## PHP code formatting

As Tuleap is mainly written in PHP, we use the PSR standards:

-   [PSR-1](https://www.php-fig.org/psr/psr-1/)
-   [PSR-12](https://www.php-fig.org/psr/psr-12/)
-   [PSR-4](https://www.php-fig.org/psr/psr-4/)

Rule of thumb: *All new classes MUST respect PSR-4*

### Javascript code formatting

Javascript files (.js) and Vue files (.vue) are parsed by two tools on a
pre-commit hook:

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

### Sass code formatting

Sass files (.scss) are also parsed by an automated tool on a pre-commit
hook. We currently use [stylelint](https://stylelint.io/) to
automatically check Sass files.

This tool will warn you when you make a mistake in a Sass rule. It will
also enforce some stylistic conventions such as using shorthand
notations or ordering the properties in rules.

This time also, feel free to configure your editor or IDE to
automatically report linting errors. This will give you the fastest
feedback. The pre-commit hook will warn you otherwise.

### Internal conventions

-   Use an indent of 4 spaces, with no tabs. This helps to avoid
    problems with diffs, patches, git history...
-   It is recommended to keep lines at approximately 85-100 characters
    long for better code readability.
-   methodsInCamelCase()
-   \$variables_in_snake_case
-   constants in UPPER_CASE
-   public methods documented (at least \@return statement)
-   class documented (`I'm responsible of…`)
-   All added code should follow PSR-12. Existing code should be
    converted to PSR-12 in a dedicated commit in order to not clutter
    the review of your functional change.
-   No trailing whitespaces
-   In DataAccessObject, convention is to name `searchXxx()` the methods
    that returns a set of rows (eg. `searchProjectsUserIsAdmin(…)`, and
    `getXxx`, `isXxx`, `hasXxx` for other cases (eg.
    `doesUserHavePermission(…)`).

### What's in my contribution ?

Contributions SHOULD NOT add/fix features AND fix coding standard of a
legacy file in the same review. The code WONT be accepted. If your eyes
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
:::

### Copyright & license

All source code files (php, js, bash, \...) must contain a page-level
docblock at the top of each file. This header includes your copyright
and a reference to the license GPLv2+ of the script.

> ``` php
> /**
>  * Copyright (c) <You>, <Year>. All rights reserved
>  *
>  * This file is a part of Tuleap.
>  *
>  * Tuleap is free software; you can redistribute it and/or modify
>  * it under the terms of the GNU General Public License as published by
>  * the Free Software Foundation; either version 2 of the License, or
>  * (at your option) any later version.
>  *
>  * Tuleap is distributed in the hope that it will be useful,
>  * but WITHOUT ANY WARRANTY; without even the implied warranty of
>  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
>  * GNU General Public License for more details.
>  *
>  * You should have received a copy of the GNU General Public License
>  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
>  */
> ```

Adapt the copyright line to your situation.
