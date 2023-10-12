# Choice of templating engine

* Status: accepted
* Deciders: Joris MASSON
* Date: from 2021-04-29 to 2021-07-22

Technical Story: [request #18391][0] Migrate Tracker Artifact Modal out of AngularJS

## Context and Problem Statement

Now that all AngularJS services, constants and values have been migrated to plain ES2015 modules, the last remaining
parts that must be changed are the directives, templates and their controllers. Which templating engine or framework
should we choose to replace AngularJS Directives ?

## Decision Drivers

* The technical story's sole reason to exist is because of our previous choice of using the framework [AngularJS]
  [6] that has since been deprecated. AngularJS can only be loaded once per page. This forbids us from including the
  modal in anything else than AngularJS apps, which is why we have been unable to add it to Taskboard, Test Plan,
  Program Management, and so on.
* The templating engine must allow easy composition. Tuleap Tracker allows users to define recursive containers, for
  example you can put a Checkbox inside a Fieldset inside a Column inside another Fieldset.
* A lot of templating code must be written. Basically (almost) every Tuleap Tracker field must be supported. Some list
  fields allow three types of binding which render differently, for example Selectbox and Multi-selectbox can be bound
  to static values (with colors), users (with avatars) or user groups (with special icon).
* The artifact modal is full of form inputs, it's basically a dynamic form. The templating engine will need to provide
  an easy way to add event listeners.
* We are in a library context that will be reused several times. Although, the size of the resulting bundle is expected
  to be "big" given the number of features supported (file uploading, markdown rendering, etc.)
* Given the size of the artifact modal (see previous points), npm dependencies number/size become less relevant to the
  choice.
* The artifact modal will be included in other Vue.js v2 apps, as well as AngularJS apps.
* The templating engine must support TypeScript. We have been using TypeScript for two years at the time of writing, and
  we intend to keep using it.
* The templating engine must support our target browser compatibility. At the time of writing, we support latest Edge,
  Firefox and Chrome, with best-effort support for Firefox 68 and Chrome 70.

## Considered Options

* [Vue.js v3][3] framework
* [lit][5] web components
* [hybrids][13] web components
* mixing web-components and Vue.js

Note: all considered options require adding some form of bridge (compatibility layer) between AngularJS and the library.

## Options disqualified by Decision Drivers

* Vanilla DOM API is not considered because of the sheer amount of code that would be needed. See for reference
  [this other ADR for rich-text-editor][1] where it was already not chosen, for a lib that was an order of magnitude
  smaller than the artifact modal. This includes Vanilla web components, for the same reason: we would need _a lot_ of
  code.
* [Mustache.js][2] is not considered because it lacks an easy way to bind events (no `v-on` or `@event`).
* [Vue.js v2][4] is not considered because [Vue.js v3][3] has been released, and we should strive not to add more code
  that will need to be migrated later. Moreover, Vue.js v2 needs several libraries to have proper TypeScript support
  and Vue.js v3 is expected to offer easier support.
* [Angular][7] (not AngularJS, the "new" one) is not considered because when we last tested it, it could still not be
  loaded more than once per page. This makes it fundamentally incompatible with the Artifact modal use-case, which
  needs to be embedded in different applications. This would forbid us from using that framework anywhere the modal
  might be called and would put us in exactly the situation we are trying to get out of.
* Web components using [shadow DOM][12] were considered but disqualified. Form inputs inside of shadow DOM do not
  register in the form and therefore do not stop users from submitting the form, which breaks the Artifact modal
  use-case. There is a proposed API to have your custom elements participate in the form
  ([Form Associated Custom Element][15]). However, I'm not 100% sure it's the same but from [Can I Use data][16] it
  looks like it is not supported by Firefox, which breaks our target browser compatibility requirement.

## Decision Outcome

Chosen option: [hybrids][13] web components, because it comes out best in the comparison and gives us flexibility to
change later.

### Positive Consequences

This choice rests upon the custom elements standard. Even if we change our mind later, we will be in a much better
position as we will be able to transparently change one component at a time. We can even change a component in the
middle of the component tree.

## Pros and Cons of the Options

### Vue.js v3 framework

[Vue.js v3][3] is the new major version of Vue.js. We have made extensive use of Vue.js v2 for all new apps since 2017.
AngularJS does not bind to Vue components. There is [a compatibility layer][20], but it seems it targets Vue.js v2, not
v3.

* Good, because we have a lot of experience with Vue.js v2, and it looks like this experience is partly transferable for
  Vue.js v3.
* Good, because it is reactive and handles re-renders automatically.
* Good, because it offers easy composition. Template is split into Components. Components may not reference themselves
  though, so we might have to find creative solutions for container fields like Fieldsets.
* Bad, because it is unknown whether Vue.js v3 will handle well inclusion into other Vue.js v2 applications.
* Bad, because [it is a Framework, not a library][9]. If we change our mind or face a limitation, we will have to
  overhaul **all the components**, starting from the "leaves". We will not be able to replace one component in the
  middle of the component tree. Vue.js v3 has split further its inner tools into reusable modules, but if we choose to
  go full Vue, it creates the possibility of having to do another long, costly migration like this one.
* Bad, because it uses its own abstraction of components, not native web components. A lib `@vue/web-component-wrapper`
  exists to output custom elements wrapping Vue components, but there are [limits to that mapping][11] (we cannot use
  some features of Vue) and it breaks the composition model we're used to. Using it could alleviate the previous
  concerns about Vue being a Framework, but it would almost forbid declaring sub-components the way we're used to. It
  would mean adhering to strict rules for component declaration just in the Artifact modal, contrary to all other
  usages in Tuleap.

### lit web components

[lit][5] is a library to help writing web components. It is the next iteration of [Polymer][8], which has been used by
Gerrit to create a full web UI based on web components. It provides a templating library (`lit-html`) and a base class
for Custom elements (formerly `lit-element`). Since we cannot use shadow DOM, we must use it as "light DOM". Since
AngularJS is older than Custom Elements, it does not bind with them. We must use [a compatibility layer][19].
* Good, because it builds upon the web components specifications. Its outputs are plain HTML (for the templating) and
  spec-compliant Custom Elements.
* Good, because it offers easy composition. Templates are built with tagged template literals that can be composed.
  Custom elements also provide easy composition: to use a component, write its tag in an HTML template with the correct
  attributes.
* Good, because [it is a library, not a Framework][9]. If we change our mind or bump into a limitation, we can replace
  each component individually, we will not end up in the same situation where we have to overhaul **everything**.
* Good, because it offers automatic re-rendering based on properties and attributes.
* Bad, because its syntax is a bit heavy. It relies a lot on `class` and [Decorators][17]. Although Decorators have been
  supported for a while in TypeScript, they are still a [stage 2 proposal][18] for Ecma TC39. As such, they can change
  significantly (and already have in the past) and are to be considered experimental. We already use Decorators for
  Vue.js v2 apps in production today, but only because it is the only way known to us to have TypeScript for Vue.js
  v2.
  It is possible to use alternate syntax instead of Decorators, but it requires developers to dig deeper in the
  documentation. Unless some static analysis rule prevents it, it is certain that people will start using them,
  exposing our apps to breaking changes.
* Bad, because we don't have as much experience with lit as we have with Vue.js, so there will be a ramp-up period.
* Bad, because we will have to come up with replacements for Vuex and vue-gettext.

### hybrids web components

[hybrids][13] is a library to help writing web components. It has a unique declarative and functional approach based on
plain objects and pure functions instead of `class` API. It provides a templating library and helper functions to ease
creation of Custom elements. Since we cannot use shadow DOM, we must use it as "light DOM".
Since AngularJS is older than Custom Elements, it does not bind with them. We must use [a compatibility layer][19].

Note: The way hybrids component work is mostly through properties. It does not reflect them to attributes. If we really
need it, [a plugin][14] offers this functionality.

* Good, because it builds upon the web components specifications. Its outputs are spec-compliant Custom Elements.
* Good, because it offers easy composition. Templates are built with tagged template literals that can be composed,
  elements are composed of plain objects and pure functions. Custom elements also provide easy composition: to use a
  component, write its tag in an HTML template with the correct attributes.
* Good, because [it is a library, not a Framework][9]. If we change our mind or bump into a limitation, we can replace
  each component individually, we will not end up in the same situation where we have to overhaul **everything**.
* Good, because it offers some reactivity and caching reminiscent of Vue.js and handles re-renders automatically.
* Good, because its syntax is very concise and flexible. Custom elements are declared based on plain objects and pure
  functions, there is no need for classes or decorators. The library's stated goal is to avoid the `class` syntax.
* Bad, because we have no experience whatsoever with that library, so there will be a ramp-up period.
* Bad, because we will have to come up with replacements for Vuex and vue-gettext. The library has a concept of Store
  that could replace Vuex.

### mixing web-components and Vue.js

[Vue.js v3][3] has split its inner workings into reusable modules. This opens the possibility of using Vue's
[reactivity][10] combined with another library for template rendering.

* Good, because we could leverage Vue's reactivity to ease management of re-renders.
* Good, because it can be tried/adopted even after choosing one of the "web component" options.
* Bad, because we have zero experience doing this. There will a ramp-up period.

## Links

* [ADR for choice of lit-html in rich-text-editor][1]
* [Vue.js v3 framework][3]
* [lit v2 templating library][5]
* [hybrids web component library][13]
* [Difference between framework and library][9]

[0]: https://tuleap.net/plugins/tracker/?aid=18391
[1]: <../../rich-text-editor/adr/0001-choice-of-lit-html-templating.md>
[2]: https://github.com/janl/mustache.js/
[3]: https://v3.vuejs.org/
[4]: https://vuejs.org/
[5]: https://lit.dev
[6]: https://angularjs.org/
[7]: https://angular.io/
[8]: https://polymer-library.polymer-project.org
[9]: https://www.freecodecamp.org/news/the-difference-between-a-framework-and-a-library-bd133054023f/
[10]: https://www.npmjs.com/package/@vue/reactivity
[11]: https://github.com/vuejs/vue-web-component-wrapper#interface-proxying-details
[12]: https://developer.mozilla.org/en-US/docs/Web/Web_Components/Using_shadow_DOM
[13]: https://hybrids.js.org
[14]: https://github.com/nullset/hybrids-reflect
[15]: https://web.dev/more-capable-form-controls/#form-associated-custom-elements
[16]: https://caniuse.com/mdn-api_elementinternals_form
[17]: https://www.typescriptlang.org/docs/handbook/decorators.html
[18]: https://github.com/tc39/proposal-decorators
[19]: https://github.com/robdodson/angular-custom-elements
[20]: https://github.com/ngVue/ngVue
