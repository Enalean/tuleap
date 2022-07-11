# JS unit test runner

* Status: accepted
* Deciders: Thomas Gerbet, Joris Masson
* Date: 2022-07-06

## Context and Problem Statement

Tuleap started to use [Jest][0] to run the JS unit tests a while ago [in replacement of Karma/Jasmine](https://tuleap.net/plugins/tracker/?aid=13806).

Jest is still the *de-facto* standard in the JS community today. However, in the recent months we started to encounter
more frequents pain-points as more JS packages started to deliver only ES Modules. At the same time credible alternatives
have emerged and are now mature enough to make them usable in the Tuleap development context.

Our [build toolchain has moved to using ESBuild][TOOLCHAIN_ESBUILD] to do the necessary code
transpilations/transformations but Jest is still using Babel. This difference and the fact the build toolchain cannot be
re-used to run the tests lead to an additional complexity in the overall system.

## Considered Options

* Keep using [Jest][0]
* Migrate to [Vitest][1]

## Decision Outcome

Chosen option: migrate to [Vitest][1] because it makes possible to move past our current pain-points  with [Jest][0].

The proposed plan to introduce Vitest into the codebase is as follows:
* new packages: Vitest is used even if Webpack is necessary to build the production bundle.
* existing packages are migrated progressively in this order:
  1. packages using Vite
  2. packages using Webpack but have their own `typecheck` task [as recommended][ADR-0010]
  3. packages using Webpack without their own `typecheck` task: they first need to have a `typecheck` task
(no typechecking will be done in Vitest) and then they can be migrated

The decision might and should be revisited in the future to see if it is still the best available option.

## Pros and Cons of the Options

### Keep using [Jest][0]

* Good, because it is the most common test runner in the JS community
* Good, because it is already what we are using so nothing needs to be done
* Bad, because we will encounter more and more frequent issues with "ESM only" JS packages as seen in our [existing configuration][EXISTING_JEST_CONFIG_ESM_WORKAROUND].
This is not something which can only be attributed to Jest, the support of ESM in the NodeJS world is not really a success story.
* Bad, because we still have to maintain 2 different build configurations for each package (one for Jest and one for building the production code)
* Note: some concerns have been raised regarding the maintenance of Jest in the recent months. Those concerns seem to
 have been resolved, at least partially, with the move from [Meta to the JS Foundation][OPENJS_JEST].

### Migrate to [Vitest][1]

* Good, because it re-uses our build configuration of Vite, no need to maintain 2 different configurations for packages
already using Vite
* Good, the "community support" appears to be good and we have a pretty good experience with our usages of Vue and Vite
* Good, the matchers API used by Vitest is compatible with Jest so there is nothing new to learn
* Bad, because we would need to migrate our existing test files. The migration is however far easier than what we experienced when
coming from Karma/Jasmine and can be progressive.
* Bad, because the IDE integrations is not yet completely there: there is *official* support for VS Code and a plugin
supported by someone in the community for Jetbrains IDEs but [no native support][VITEST_JETBRAINS_FEATURE_REQUEST].

[0]: https://jestjs.io/
[1]: https://vitest.dev/
[TOOLCHAIN_ESBUILD]: https://tuleap.net/plugins/tracker/?aid=20149
[ADR-0010]: ./0010-ts-typechecking-individual-task.md
[EXISTING_JEST_CONFIG_ESM_WORKAROUND]: https://tuleap.net/plugins/git/tuleap/tuleap/stable?a=blob&hb=dd97b0182d5b1b1130f1537f964e062618db3936&h=f6004aa9f8395b23a1b92bacdf7b1879950a51c5&f=lib%2Ffrontend%2Fbuild-system-configurator%2Fsrc%2Fjest%2Fbase-config.ts#L110
[OPENJS_JEST]: https://openjsf.org/blog/2022/05/11/openjs-foundation-welcomes-jest/
[VITEST_JETBRAINS_FEATURE_REQUEST]: https://youtrack.jetbrains.com/issue/WEB-54437/Support-Vitest-as-a-test-framework