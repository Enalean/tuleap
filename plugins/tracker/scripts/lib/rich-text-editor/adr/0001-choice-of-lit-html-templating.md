# Choice of lit-html templating

* Status: accepted
* Deciders: Joris MASSON, Clarck ROBINSON
* Date: 2021-04-01

Technical Story: [story #18337][0] markdown replace text

## Context and Problem Statement

Rich-text-editor library lets users choose a text format between "HTML" with a CKEditor instance with WYSIWYG and
"Markdown" with a raw textarea (and "Text" for older comments / text fields with only a raw textarea). In "Markdown"
format, there are two modes. In "Edit" mode, you write the source Markdown text. When you press the "Preview" button,
there is an asynchronous call to Tuleap API to convert the Markdown text to HTML string with references interpreted.
During this call, all interactive elements (button, textarea, format selectbox) should be disabled, and a loading "spinner"
should be shown. When the call succeeds, the resulting HTML is shown in a "Preview area", and the textarea is hidden.
When you click again on "Edit" the "Preview area" is hidden, and the textarea is shown again. It must also handle error
state and offer the user to retry.

Given this context, how do we render our DOM ?

## Decision Drivers

* We were previously using "with Vanilla DOM API" option.
* We are in a "library" context that aims to be reused several times.

## Considered Options

* with Vanilla DOM API
* with [Mustache.js][2]
* with [lit-html][1]
* with [Vue.js v2][3]

## Decision Outcome

Chosen option: "with lit-html", because it is the option that comes out best (see below).

Given the amount of interactions and "showing/hiding" things:
- Vanilla DOM API seemed too unwieldy. There was already a lot of code and much more was needed.
- Mustache.js did not offer easy syntax for binding event handlers.
- Vue.js seemed overkill. Vue.js would have doubled the bundle size but there is "only" one interactive button and one
  selectbox so it was not worth it.

### Positive Consequences

* We gain more experience in using lit-html

### Negative Consequences

* Future contributors will have to learn the lit-html syntax and "way of doing things". It adds to the ramp-up time.

## Pros and Cons of the Options

### with Vanilla DOM API

* Good when there are few interactions and few elements to render
* Good, because it introduces no NPM dependency
* Good, because it is native API, so it does not add to the lib size
* Bad, because given the interactions (disabling stuff, hiding/showing stuff), it becomes hard to write and maintain
* Bad, because it is very verbose, we have to write much more code than other options
* Bad, because it is relatively easy to introduce XSS (just set `.innerHTML` property)

### with Mustache.js

* Good, because it makes it harder to introduce XSS
* Good, because we have experience writing Mustache code in PHP (and a little experience with JS)
* Good, because it has a small size
* Bad, because it introduces another NPM dependency to maintain, upgrade, etc.
* Bad, because there is no easy way to bind events in Mustache Syntax (no `v-on` or `@event`)
* Bad, because it re-renders all the DOM subtree which has poor performance even if it caches the parsed template
* Bad, because we have to call the `render()` method manually

### with lit-html

* Good, because it makes it harder to introduce XSS
* Good, because it has very good performance on re-renders
* Good, because it has an easy way of binding events (`@input=${handler}`)
* Good, because it has a small size and can be tree-shaken (it is an ES Module)
* Bad, because it introduces another NPM dependency to maintain, upgrade, etc.
* Bad, because we have little experience using it (it was only used in ListPicker), so contributors need to learn the syntax
* Bad, because we have to call the `render()` method manually

### with Vue.js v2

* Good, because it makes it harder to introduce XSS
* Good, because we have a lot of experience using it
* Good, because it has very good performance on re-renders
* Good, because it has an easy way of binding events (`v-on:event`)
* Good, because it is reactive and handles re-renders automatically
* Bad, because with TypeScript it introduces not one but several NPM dependencies to maintain, upgrade, etc.
* Bad, because it has a much bigger size than other options, which is bad in the context of a library re-used at several
  places in Tuleap.
* Bad, because Vue.js v3 has been released, and we add more code that will probably need to be migrated.
* Bad, because the `rich-text-editor` library is included in contexts where Vue.js is already present. This means that
  we can have situations with two different Vue trees and a Vue app inside another Vue app. From our experience with
  Tracker workflow and components from other plugins, it does not work well and is pretty much a mess.

## Links

* [lit-html templating library][1]
* [mustache.js templating library][2]
* [Vue.js framework][3]

[0]: https://tuleap.net/plugins/tracker/?aid=18337
[1]: https://lit-html.polymer-project.org/
[2]: https://github.com/janl/mustache.js/
[3]: https://vuejs.org/
