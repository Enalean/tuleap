# Link selector becomes Lazybox, a generic auto-completer.

* Status: accepted, updates [ADR-0001: Link selector component][4]
* Deciders: Joris MASSON, Thomas GORKA
* Date: 2023-04-06

Epic: [epic #28288][1] Revamp of Pull Request Comments

## Context and Problem Statement

The overview part of the angular "pullrequests-app" is currently being rewritten in an external Vue3 app.
This app contains two autocompleted selectors: one to select some reviewers, and one to assign it some labels.

We currently have nothing else than [Select2][2] to fetch values on-the-fly, to display them and allow multi-values selections.

The problems are:
- We want to reduce the usage of [Select2][2], which is always a pain to use.
- Because of the way Select2 works, we are forced to compute crazy diffs in order to know what are the added and the removed values.
- Even though there are some types for Select2, they are impossible to use without having [jQuery][3] loaded in the app.
- We also want to get rid of [jQuery][3].

Hence, a replacement for Select2 is required.

## Considered Options
* Find and use open-source auto-completer libraries
* Implement the missing functionalities in [Link-selector][4]

## Pros and Cons of the Options

### Find and use open-source auto-completer libraries

Some auto-completer libraries can be found on GitHub and can be used in our apps.

* Good, because it's less initial work as we don't have to write code.
* Bad, because those auto-completer libraries target specific frameworks like Vue.js or Angular. We do not want to maintain different kinds of auto-completer libraries in the codebase.
* Bad, because usually the community using those libraries often want features specific to their use-cases to be implemented. They frequently become bigger and bigger, gaining tons of options over time.
* Bad, because if a breaking change is introduced, we will be stuck with an old version of this lib, and we will be forced to find a new alternative.

### Implement the missing functionalities in [Link-selector][4]

* Good, because it is our home-made library.
* Good, because it is perfectly tailored to our use-cases: no more, no less.
* Good, because it is written in TypeScript, and thus, it can be used everywhere.
* Bad, because it needs some work to make it complete.

## Decision Outcome

[Link-selector][4] being already a generic auto-completer for single values, and not particularly a link selector, it needs only a few things to make it work with multiple values as well:
* A multiple-values selection behavior.
* An `is_multiple` option to toggle it.

Since `link-selector` is a too specific name, we have decided to rename it `lazybox`.

The name `lazybox` has been chosen because we think it reflects pretty well the spirit of this component:
* First display an empty box featuring a search input and a dropdown.
* Then, push values in its dropdown as you please.
* It is pretty easy to implement in the various Tuleap features needing an auto-completer.

### Recommendations and rules

* When an auto-completer is needed in a feature, either during a refactoring or a brand-new app, use `lazybox`.

### Positive Consequences

* It allows us to remove progressively [Select2][2].
* It allows us to reduce dependencies to [jQuery][3].
* It is less time-consuming and less error-prone to implement an auto-completer.

### Negative consequences

* Some work is needed to cover the different use-cases: handle multiple values, new value creation, display of colored badges.
* A renaming of the whole library is once again required.

[1]: https://tuleap.net/plugins/tracker/?aid=28288
[2]: https://select2.org/
[3]: https://jquery.com/
[4]: ./0001-link-selector.md
