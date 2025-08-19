---
status: accepted
date: 2025-07-17
decision-makers: Jacques FIADEHOUNDJI, Marie Ange GARNIER, Joris MASSON
consulted: nobody
informed: Thomas GORKA, Thomas GERBET, Nicolas TERRAY, Clarck ROBINSON, Martin GOYOT, Kevin TRAINI, Manuel VACELET, Clarisse DESCHAMPS
---

# Choosing Vitest Snapshot to test Graphs

## Context and Problem Statement

[story #42227: Have pie chart graph][0]

We are reworking the Graphs based on Trackers. We want to use [Storybook][8] to help us test and maintain the [D3 code][7] responsible for creating the graphs. Storybook gives us a quick feedback because we can work on the code and see the result quickly for different test cases (no data, many groups, only one group, etc.). We want to go a step further and have automated tests for our graphs.

We want to run automated component tests inside Storybook, directly via the CLI, as part of our CI Pipeline. Our initial solution using [@storybook/test][2] was limited: tests dit not run in CLI mode, leading to potential silent failures that can go unnoticed in CI environment.

We iteratively evaluated different tools and strategies before settling on a compatible solution. This ADR documents why we choose **Vitest snapshot testing** (`toMatchSnapshot`) for visual component testing.

## Decision Drivers

* Need for Storybook component testing that works **headlessly via CLI**
* Avoiding **silent failures** (as with [@storybook/test][2])
* Ensuring **compatibility** with our current Storybook version ('8.4')
* Avoiding dual e2e systems because we already use Cypress for end-to-end tests (see [previous decision][1]), so adding another tool like Playwright would increase maintenance complexity
* Avoiding Playwright as e2e system because of its **instability on Fedora**
* Desire for fast, simple, lightweight visual testing of components

## Considered Options

* [@storybook/test][2]
* [Storybook Vitest addon][3]
* [@storybook/test-runner][4] (with Playwright)
* [Storybook testing with Cypress][5]
* [Vitest][6] with `toMatchSnapshot()`

## Decision Outcome

**Chosen option: [Vitest][6] with `toMatchSnapshot()`**, because it offers the simplest and fastest solution for visual testing of components, runs perfectly in CLI and CI, is compatible with Storybook 8.4 and does not require a full browser environment. Snapshots are saved locally through Git and can automatically be checked during tests. This helps us catch and review any unexpected UI changes easily.

### Consequences

* Good, because it’s lightweight and runs fast in CI
* Good, because tests fail visibly when UI changes unexpectedly
* Good, because it requires minimal setup and no browser or Storybook server
* Good, because snapshot diffs are readable
* Bad, because it doesn’t test in a real browser like Cypress or Playwright (but acceptable)

### Confirmation

Tests for Graphs use [Vitest][6] with `toMatchSnapshot()`.

## Pros and Cons of the Options

### [@storybook/test][2]

* Good, because it's integrated in Storybook
* Bad, because it doesn't run in CLI (silent bug possible) and not suitable for CI automation

### [Storybook Vitest addon][3]
* Good, because it runs in CLI
* Bad, because it's **incompatible** with our current Storybook v8.4 (it requires v9)
* Bad, because it is still experimental and not widely adopted

Rejected for version mismatch

### [@storybook/test-runner][4] (with Playwright)
* Good, because it's designed to test components in Storybook via CLI
* Bad, because it requires Playwright which is not supported on Fedora natively
* Bad, because it adds a second e2e system to maintain

Rejected due to compatibility issues with Playwright in our environment.

### [Storybook testing with Cypress][5]

We manually configure Cypress with [cypress-storybook][9] library to target our Storybook instance and use it to test UI components in isolation.

* Good, because it reuses Cypress already present in the project (see the [previous decision][1])
* Good, because we control the setup (run Cypress against `localhost:6006`)
* Good, because it runs in a real browser
* Bad, because Cypress can be heavier than other unit-level tools

Rejected in favor of lighter and faster snapshot testing via Vitest

### [Vitest][6] with `toMatchSnapshot()`

* Good, because it runs fast in CLI and CI
* Good, because it integrates naturally into Vitest
* Neutral, because snapshots can be versioned
* Bad, because it doesn't run in a full browser (but acceptable in our case since we are testing static SVG graphs, where no interactive events like clicks or hovers need to be simulated).

## More Information

* [@storybook/test][2]
* [Storybook Vitest addon][3]
* [@storybook/test-runner][4] (with Playwright)
* [Storybook testing with Cypress][5]
* [Vitest][6] with `toMatchSnapshot()`
* Graph on Trackers plugin's [ADR-0001: Choice of library to draw graphs][7]
* [ADR-0027: Choice of tool to present the Components documentation][8]
* [ADR-0014: JS unit test runner][10]

[0]: https://tuleap.net/plugins/tracker/?aid=42227
[1]: https://tuleap.net/plugins/tracker/?aid=11219
[2]: https://storybook.js.org/docs/writing-tests/interaction-testing
[3]: https://storybook.js.org/docs/writing-tests/integrations/vitest-addon
[4]: https://storybook.js.org/docs/writing-tests/integrations/test-runner
[5]: https://storybook.js.org/docs/writing-tests/integrations/stories-in-end-to-end-tests#with-cypress
[6]: https://vitest.dev/api/expect.html#tomatchsnapshot
[7]: ../../../graphontrackersv5/docs/decisions/0001-choice-of-library-to-draw-graphs.md
[8]: ../../../docs/decisions/0027-component-documentation.md
[9]: https://storybook.js.org/addons/cypress-storybook
[10]: ../../../docs/decisions/0014-js-unit-test-runner.md
