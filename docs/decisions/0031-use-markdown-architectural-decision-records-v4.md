---
status: accepted
date: 2024-11-05
decision-makers: Joris MASSON
consulted: Manuel VACELET, Thomas GERBET, Nicolas TERRAY
informed: Thomas GORKA, Thomas GERBET, Nicolas TERRAY, Clarck ROBINSON, Marie Ange GARNIER, Kevin TRAINI, Manuel VACELET, Clarisse DESCHAMPS
---

# Use Markdown Architectural Decision Records v4.0.0

## Context and Problem Statement

Since [the first ADR][0] we wrote in 2021, we have been using the [MADR][3] template for Architectural Decision Records at [version 2.1.2][1]. We didn't document it at the time, but the template text matches exactly our copy. Since then, the MADR template has seen a number of revisions that could be interesting for us.
Which format and folder structure should our ADRs follow ?

[request #40439 Adopt MADR v4 template for ADRs][6]

## Considered Options

* Switch to [MADR v4.0.0][4]
* Status quo: Keep using [MADR v2.1.2][1]

## Decision Outcome

Chosen option: "Switch to MADR v4.0.0", because it comes out best (see below).

* It brings interesting changes to the template, such as the "Confirmation" section to incite ADR writers to think about how/when a decision can be considered "done".
* It simplifies the template: "Positive Consequences" and "Negative Consequences" are merged together.
* Merging the `adr/` folder with the `docs/` folder could help people find information on our standards and why they exist more easily.
* Meaningless `index.md` files are renamed to `README.md`, which is much more common.

Additionally to this format, links in ADR files should be written using the [Reference style][5] (for example: `[0]: https://example.com`) at the end of the document. In almost all cases, it makes it easier to read the plain Markdown text with no impact on the rendered HTML.

### Consequences

* Good, because it should improve the quality of future ADRs.
* Bad, because the `adr-log` program (that we used to generate the links to each ADR) seems to choke on the YAML front-matter that is now part of the template (the top section, between `---`). As no new version has been released since 2020, and we are not willing to fork it or take up its maintenance, we choose to stop using it. New ADR links must be added manually to README pages.

### Confirmation

All `template.md` files match the [MADR v4.0.0 adr-template.md][4] (the "full" template with all sections and explanations). There are no longer any `adr/` folders. There are `docs/decisions/` folders in all libraries, plugins or places that use ADR documents. The main files in each `decisions/` folder are named `README.md` instead of `index.md`.

## More Information

* MADR v4.0.0 [Changelog][2]
* Guidelines for folder structure and file names can be found here: https://adr.github.io/madr/#initialization
* Markdown Guide for [Reference-style links][5]
* This decision is expected to be revised/superseded in the future, for example if the MADR template is revised again, and it brings some improvement.

[0]: 0001-supported-browser-versions.md
[1]: https://github.com/adr/madr/blob/2.1.2/template/template.md
[2]: https://github.com/adr/madr/blob/4.0.0/CHANGELOG.md#400--2024-09-17
[3]: https://adr.github.io/madr/
[4]: https://github.com/adr/madr/blob/4.0.0/template/adr-template.md
[5]: https://www.markdownguide.org/basic-syntax/#reference-style-links
[6]: https://tuleap.net/plugins/tracker/?aid=40439
