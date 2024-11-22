# Choice of tool to present the Components documentation

* Status: accepted
* Deciders: Joris MASSON, Lou-Evan ROUBY
* Date: from 2023-07-21 to 2024-03-18

Technical Story: [request #37211 Replace TLP-doc documentation][0]

## Context and Problem Statement

In a previous decision around 2015 (before writing ADRs), we built our own design system and global library called [tlp][4]. Sharing it to everyone in the maintainer team was essential for its success. Developers needed a solid documentation explaining how to use its components, functions and CSS classes, how to assemble components into pages, how to build page layouts, etc. We chose to build our own documentation engine, with the tools we had on hand: PHP, HTML, CSS and JavaScript (TypeScript was virtually non-existent at that time).

Up until now, this documentation has served us well: everyone in the Tuleap maintainers team is quite familiar with TLP, page layout, colors, modals, etc. We even included a few dev utilities in the doc because it was a central point of reference. However, recently two new groups of people have started requesting access to this doc:
1. Developers outside Tuleap contributors team. For example, developers working on external integration to Tuleap (such as Mediawiki).
2. Non-developers working on Tuleap. For example, product designers.

In its current state, the TLP documentation is heavily coupled to Tuleap itself. The only way to consult it is to have a fully functional Tuleap development stack (see the option "Keep `tlp-doc`" for more details on why). This is very difficult for people outside Tuleap contributors group. Both of the groups listed above usually lack such a development environment because they do not need it, or do not have the technical skills and experience with the tools required to bring it up and keep it working.

How can we give access to an up-to-date and more easily accessible documentation to those groups?

## Decision Drivers

* To access the documentation, you should not need to set up a fully functional Tuleap development environment. It should be possible to host the documentation on a website controlled by us, without security issues.
* Using `git clone` and maybe a couple of commands is acceptable.
* The tool should support custom elements (for example Artifact modal fields) as well as "function libraries" (for example TLP dropdown).
* Editing the documentation should be simple. We should not need to add / edit a lot of files to add a component.
* We should be able to configure and try the many variants of a component, so that we can check that a component modification "works" in all variations.
* Being able to configure a component with buttons is more important than the possibility of editing code directly.

## Considered Options

* Keep `tlp-doc`
* [Storybook][1]
* [PatternLab][10]
* [Fractal docs][11]

## Options disqualified by Decision Drivers

* [Docz][6] is easy to learn and configure. It also offers an interactive playground. It can't be considered because the existing version is no longer maintained and there is no information for now about when the next one will be released.
* [Histoire][7] was made for Vue and Svelte. It does not support custom elements. It offers fewer possibilities than Storybook.
* [Ladle][8] and [React cosmos][9] both seem interesting tools but were made for React components. They do not support custom elements.

## Decision Outcome

Chosen option: [Storybook][1].

If we keep `tlp-doc`, we can't resolve all issues, it seems better to create a new documentation.\
[Storybook][1] comes out best in the comparison (see below). It seems there is no tool as complete and supporting many tech stacks.

### Positive Consequences

See the Pros and Cons of the Options section for [Storybook][1].

### Negative Consequences

* Writing Stories for all tlp components (and "list-picker", "lazybox", and others) will take some effort and some time. In the meantime, we will have to work with two sources of truth:  Storybook for some components, and `tlp-doc` for others.
* There will be more dev dependencies to keep up-to-date.

## Pros and Cons of the Options

### Keep `tlp-doc`

[tlp-doc][3] is an internal custom app, initially built to showcase our internal [tlp][4] library. It started out as a "docs" sub-folder in the same folder as the tlp "theme" folder. Then it was split out to allow for saner dependencies management, since it started showcasing elements outside tlp itself. It includes a PHP backend (handled by the Tuleap stack, so nginx → PHP FPM → Fast Route → Tuleap Front Router) and a frontend part, written in JavaScript, as well as custom CSS. It is structured by our homebrew "framework" with pages and sections described by `manifest.json` files and implemented by a combination of `example.html`, `doc.html` files and JavaScript code bundled in ` editors.js`. It also features [CodeMirror][5] editors that showcase example code, but are also editable and reflect back their content to the page. This feature in its current implementation is a security hazard (Self-XSS) and forbids publishing `tlp-doc` online.

* Good, because it worked well as a documentation and reached its goals to bring developers on board with the way Tuleap frontend should be built.
* Bad, because it requires a fully functional Tuleap development stack to browse it. Its backend is entirely reliant on Tuleap code and conventions (such as asset file names including hashes).
* Bad, because the above point means non-developers cannot consult those docs, as it is very difficult for them to bring up a development setup.
* Bad, because to resolve this issue, we should basically rewrite a significant part of the backend to be able to extract it from the Tuleap "framework" and run it in isolation.
* Bad, because even after doing this work, people wanting to browse it would still need developer tools to consult it: probably a PHP server, or Docker to run a docker image.
* Bad, because even if we manage to do the work to allow it to be hosted on a static server, the CodeMirror editors would still pose a security threat for people browsing those docs. They would need to be disabled.
* Bad, because finding the code of the desired variation of a component is not efficient. You have to look in a long scrollable CodeMirror editor until you find the right snippet.

### [Storybook][1]

Storybook is a tool to help implement UI components in isolation. Developers write components, then write render functions to wire them up with properties and callbacks. Each "interesting" state of the component is called a "story". There is a documentation side to Storybook: it is possible to write documentation in Markdown and include Stories in it. It produces an interactive and up-to-date documentation of UI components. Stories can not only help developers implement, test and fine-tune specific UI component states, but can also document the possible states and why they exist.

* Good, because it contains its own dev server. It is a single "watch" to show all components and work on them.
* Good, because it can generate a static site composed of plain HTML, CSS and JavaScript. This static site can then be uploaded on a web server regularly to stay up-to-date.
* Good, because it is well-maintained and documented.
* Good, because it has a large user community.
* Good, because it has a good user-interface.
* Good, because it allows to run tests on UI component states.
* Bad, because it can be quite slow to start.
* Bad, because existing documentation has to be converted to Storybook.

### [PatternLab][10]

* Good, because it can be adapted to different tech stacks, and it includes a static site generator.
* Bad, because it is less focused on interactions and component testing. It is more about design.

### [Fractal docs][11]

* Good, because it can be easily exported into a static page.
* Bad, because as [PatternLab][10], it does not offer testing tools as [Storybook][1] does.

## Links

* [ADR-0019: Split `tlp` global library in small packages][4]

[0]: https://tuleap.net/plugins/tracker?aid=37211
[1]: https://storybook.js.org/
[3]: https://tuleap.net/plugins/git/tuleap/tuleap/stable?a=tree&hb=136207d9c5dbab30ea2f00d20f1cc09e9f2a72be&f=src%2Fscripts%2Ftlp-doc
[4]: 0019-split-tlp.md
[5]: https://codemirror.net/
[6]: https://www.docz.site/
[7]: https://histoire.dev/
[8]: https://ladle.dev/blog/introducing-ladle/
[9]: https://reactcosmos.org/
[10]: https://patternlab.io/
[11]: https://fractal.build/
