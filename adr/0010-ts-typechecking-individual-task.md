# TypeScript typechecking in individual task

* Status: accepted
* Deciders: Thomas Gerbet, Joris Masson
* Date: 2022-01-13

## Context and Problem Statement

Typechecking of TypeScript files is currently mixed with the `build` and `test` tasks.
While this makes things easy for developers contributing to an existing package because they cannot forget about the
type system, it comes with some issues:
* some files are typechecked twice, once during the execution of `build` task and once during the execution of `test`
task which costs us some CPU time
* it is harder to troubleshoot some typechecking edge cases, some issues only show up under specific `build` or `test`
conditions
* it is less easy to integrate specific typechecking tools like [`vue-tsc`](https://github.com/johnsoncodehk/volar/tree/master/packages/vue-tsc)
with our build tools


## Considered Options

* [option 1] Keep current process as is
* [option 2] Move the typechecking into its own task for each package

## Decision Outcome

The typechecking is moved into its own task for each package.

### Positive consequences

* It will improve the performance of the CI pipeline since we will not typecheck multiple times the same files
* We are less likely to encounter errors only in specific scenario
* We can easily switch the tool used for typechecking to choose the one the best suited to the situation


### Negative consequences

* We have to go through each package of the codebase to do the change
