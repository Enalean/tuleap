# Split `tlp` global library in small packages

* Status: accepted
* Deciders: Thomas GERBET, Romain LORENTZ, Clarck ROBINSON, Thomas GORKA, Joris MASSON, Nicolas TERRAY, Manuel VACELET, Yannis ROSSETTO
* Date: 2022-04-01

Technical Story: [request #29982 Split `tlp` in smaller packages][0]

## Context and Problem Statement

In a previous decision around 2015 (before writing ADRs), we chose to break free of our dependencies on many global libraries ([Prototype][1], [Bootstrap v2][2], [jQuery v1][3], etc.). Tuleap then supported multiple "themes" and users could customize them. The main theme was then called "FlamingParrot" (or "FP"). To do that, we started a new theme called "BurningParrot" (or "BP"). We chose to write our own design system and universal global library, called "tlp" (remove all vowels from Tuleap).

Since that time, ECMAScript modules and [module bundlers][4] appeared. They allowed us to stop relying on "global variable"-style libraries. The global `tlp` library is now causing problems. Because it is a global variable, it is harder to search where it is used. Making changes to basic parts (such as the style of modals) leads to giant reviews that no-one wants to take on. It makes it hard to "swap" components.

## Decision Outcome

We will split `tlp` in smaller modules: `@tuleap/tlp-modal` for the modals, `@tuleap/tlp-fetch` for the `fetch` wrapper API, etc. Each module will be a Library (as defined by [ADR-0016][5]).

### Positive Consequences

* It is much easier to deprecate, replace and eventually delete smaller libraries than even a part of a mega-library. For example, since [ADR-0013](./0013-neverthrow.md), we have an alternative to `@tuleap/tlp-fetch`: `@tuleap/fetch-result`. It is possible to replace usage of the former by the latter progressively, on small-scale refactorings.
* Tracking usage is much easier. We can grep on the package name, for example we can search `@tuleap/tlp-fetch`. It is also mandatory to declare package dependencies in `package.json`, letting us track usage again.
* We may stop loading on every page a lot of code that will probably not be used. However, that requires going to the end of the process and finally removing the big `tlp` global variable.

### Negative Consequences

* On pages with many Applications (as defined by [ADR-0016][5]) such as the Trackers Artifact view, modules that are often used such as `@tuleap/tlp-fetch` will be duplicated for each application. However, since our module bundlers apply tree-shaking, the size increase should be limited. The modules themselves are quite small too. If this becomes a bigger problem, we can explore module bundler features such as "module federation".

## Links

* [ADR-0016: Independent libraries for shared frontend code][5]
* [request #18842 Split TLP into separate smaller libraries](https://tuleap.net/plugins/tracker/?aid=18842)
* [request #20906 Split TLP popovers to its own library](https://tuleap.net/plugins/tracker/?aid=20906)
* [request #26374 Stop exposing @tuleap/tlp-fetch module in tlp module](https://tuleap.net/plugins/tracker/?aid=26374)
* [request #26742 Split TLP dropdowns to its own library](https://tuleap.net/plugins/tracker/?aid=26742)

[0]: https://tuleap.net/plugins/tracker/?aid=29982
[1]: http://prototypejs.org/
[2]: https://getbootstrap.com/2.3.2/
[3]: https://jquery.com/
[4]: 0018-js-bundler.md
[5]: 0016-frontend-libraries.md
