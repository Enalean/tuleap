# Supported browser list

* Status: accepted
* Deciders: @vaceletm @jmasson @tgerbet
* Date: 2021-04-20

## Context and Problem Statement

After upgrade to Tuleap 12.7, some users faced issues with legacy browsers (Chrome 70) because our [browserslist](../lib/frontend/build-system-configurator/src/browserslist_config.ts)
was set to "last 2 Chrome versions,last 2 Firefox versions,Firefox ESR,last 2 Edge versions" so:
* chrome 87, 88, 89
* edge 88, 89
* firefox 78, 85, 86

The transpiler then used Javascript features that were not available in Chrome 70 so all javascript features led to an error.

We had to make an emergency patch to re-introduce Chrome 70 in the target list.

In addition to Chrome 70, we also know that usage of Firefox 68 is also widespread by some key users.

## Decision Drivers

* Supporting old browsers come with a cost during development (need to check if features are available on [Can I Use](https://caniuse.com/), find polyfill if any, etc).
* Supporting old browsers is a security concern for end users. They run vulnerable tools and can easily be targeted by cyberattacks.
* Some companies are slow to upgrade their tool. End users doesn't have control on their computer to upgrade, IT must coordinate so old "IE6 only" intranets are still working, etc.

## Considered Options

* [option 1] Update `browserslist` to add Chrome 70 and Firefox 68.
* [option 2] Stick with "modern browsers" list.
* [option 3] Create a "LTS" version of Tuleap with support of vulnerable browsers.

## Decision Outcome

Chosen option: "[option 1] Update `browserslist` to add Chrome 70 and Firefox 68", because it's the best tradeoff on the table:
* The [official supported version of browsers](https://docs.tuleap.org/user-guide/misc.html#which-browser-should-i-use-to-browse-tuleap) doesn't change: last version of Firefox, Chrome and Edge. That means
  * Chrome and Firefox are actively tested
  * Bugs are fixed.
* Firefox 68 and Chrome 70 are supported in "best effort". That means:
  * Those versions **are not tested** during development or validation
  * It's the duty of the companies that deploy Tuleap in such environments to carefully run tests.
  * If a user report an issue on an old feature, development team do whatever is best for itself: feature guard, polyfill, etc.
* The release in 6 months (Tuleap 12.13, due the 15th September 2021) will be the last to run on Firefox 68 and Chrome 70.
  * The minimal version for "best effort" will be bumped to Firefox 78 and Chrome 87.
  * Companies that cannot meet those minimals should no longer update Tuleap.

### Positive Consequences

* Clearer rules of support for development team
* Better awareness of the minimal supported version for companies

### Negative Consequences

* More relax `browserslist` means that more users can experience random bugs due to their legacy browser not supporting features.
  * Random bugs means more support effort from the development team.
* Users continue to use vulnerable browsers despite the security risks.
* Users that does their job of running up to date browsers doesn't benefit from the performance and reduced size of generated assets (Javascript)
* Developers continue to have slow build time

## Pros and Cons of the Options

### [option 2] Stick with "modern browsers" list

Stick with "modern browsers" list. Aka only the 2 last versions of each browser.

* Good, because it forces users to upgrade their browsers
* Good, because generated javascript is smaller and more efficient.
* Good, because build time for developers is shorter.
* Bad, because Tuleap no longer evolves for users with old browsers.
* Bad, because companies that cannot upgrade their browsers are just stuck and either cannot upgrade Tuleap or upgrade and losses a large share of their users.

### [option 3] Create a "LTS" version of Tuleap with support of vulnerable browsers

Creating an LTS for old browsers support

* Good, because users with sane browser practices have a more efficient experience of Tuleap (generated js faster and smaller)
* Good, because users with old browsers can continue to use Tuleap with support (bug fixing)
* Bad, because the cost of maintenance is huge. The more it last, the more it costs.
