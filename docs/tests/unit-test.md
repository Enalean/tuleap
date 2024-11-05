# Unit tests

## PHPUnit

### Run tests in cli

Tuleap comes with a handy test environment, based on PHPUnit. Core tests
(for things in src directory) can be found in tests/phpunit directory
with same subdirectory organization (eg.
`src/common/frs/FRSPackage.class.php` tests are in
`tests/phpunit/common/frs/FRSPackageTest.php`). Plugins tests are in
each plugin tests directory.

To run tests you can either use multiple CLI commands (at the root of
Tuleap sources):

-   make phpunit-docker-73
-   make phpunit-docker-74

### Helpers and database

**A bit of vocabulary**

Interactions between Tuleap and the database should be done via
`DataAccessObject` (aka. dao) objects (see
`src/common/dao/include/DataAccessObject.class.php`) A dao that returns
rows from database wrap the result in a `DataAccessResult` (aka. dar)
object (see `src/common/dao/include/DataAccessResult.class.php`)

Tuleap provides a class `TestHelper.class` who will help you
to ease interaction with database objects.

## Jest unit tests

[Jest](https://jestjs.io/) is the Javascript testing framework to write
down our JavaScript unit tests.

You must provide some unit tests for any front-end development.

In this section you will find guidelines on how to setup your own Jest
tests.

### How to bootstrap your unit tests

First, it is necessary to have a Jest configuration file named
`jest.config.js`. This config file is pretty easy to set up.

``` JavaScript
// tuleap/plugins/<your_plugin>/jest.config.js

const base_config = require("../../tests/jest/jest.base.config.js");

module.exports = {
    ...base_config,
    displayName: "<your_plugin>"
};
```

You will then need to add a test script in your `package.json` file to
launch Jest when `pnpm test` is used.

``` JavaScript
// tuleap/plugins/<your_plugin>/package.json
{
    //...
    "scripts": {
        //...
        "test": "jest"
        //...
    }
}
```

### How to debug tests

If you are using an IDE from JetBrains you should be able to run the
tests and add breakpoints out of the box. For more information you can
consult the [Jest documentation about debugging in
WebStorm](https://jestjs.io/docs/troubleshooting#debugging-in-webstorm).

For others tools, like VS Code check out the [Jest
documentation](https://jestjs.io/docs/troubleshooting#debugging-in-vs-code).

### Best-practices for Tuleap

When you submit a patch for review, we may request changes to better
match the following best practices. Please try to follow them.

-   Always name unit test files with the same name as their test subject
    and suffixed with `.test.ts`. For example:
    `form-tree-builder.test.ts` tests `form-tree-builder.ts`,
    `DocumentBreadcrumb.test.ts` tests `DocumentBreadcrumb.vue`.
-   Always put unit test files next to their test subject, in the same
    folder. See [Angular.js Style Guide
    rule](https://github.com/johnpapa/angular-styleguide/blob/master/a1/README.md#style-y197)
    for reasons why having unit tests close to the source is a good
    idea.

### Resources

-   [Angular.js Style Guide
    rule](https://github.com/johnpapa/angular-styleguide/blob/master/a1/README.md#style-y197)
    related to unit test file location.
-   Google Best Practice Recommendations for Angular App Structure:
    <https://docs.google.com/document/d/1XXMvReO8-Awi1EZXAXS4PzDzdNvV6pGcuaF4Q9821Es/pub>
-   React File Structure recommendation:
    <https://reactjs.org/docs/faq-structure.html>

The Vue.js community has no recommendation at the time of writing. Some
projects write unit tests in a separate folder hierarchy, some write
them side-by-side with source files. We chose the latter for reasons
outlined in the [Angular.js Style Guide
rule](https://github.com/johnpapa/angular-styleguide/blob/master/a1/README.md#style-y197).
