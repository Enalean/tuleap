# Link selector component

* Status: Updated by [ADR-0002: Link selector becomes Lazybox, a generic auto-completer.][5]
* Deciders: Joris MASSON, Thomas GORKA
* Date: 2022-04-06

Technical Story: [story #24969][1] [modal] add and remove links (replace current artifact link)

## Context and Problem Statement

[The new artifact link field][1] will feature an "auto-completer" component. It allows users to filter a list of recent artifacts (or potential parent artifacts) and choose one of the filtered items to create a new link. It will also let people choose a special option "Create a new artifact…". When clicked, this option will have a different behaviour and open a short in-line form to create a new artifact. In a future story, this auto-completer will also let users search for artifacts (using the same input field as filtering).

There are several paths to implement this component, which one should we choose ?

## Decision Drivers

* There are several groups of "options" with different behaviours. For example, if I chose the type of link "Is Parent of", the auto-completer will show a list of "potential parent" artifacts. If I chose another type of link, this list is not shown. If the current artifact already has a parent, this list is not shown either. There is also a "recent items" group. In the future, there will also be a "Search results" group. All of them behave differently: the "Search results" are fetched asynchronously from whatever the user wrote in the "filter" `<input>`. The other two groups are fetched when the field is loaded, but writing in the "filter" `<input>` will filter items in each group.
* There are a lot of asynchronous calls going on, the component must handle a "waiting" state and an "error" state if the calls fail for any reason.
* For each group of "options", clicking on a list item will select it and enable a button to add a new link.
* There is a "special" button-like option "Create a new artifact…". It has different behaviour from the others. When clicked, it does not select its value, but instead it will open a small form to create a new artifact in-line. It must always be at the top of the options.
* In the intermediate state before the story that allows to search for artifacts, users must be able to enter any artifact identifier and be able to link it. To handle this, when the entered text is a number, an asynchronous request is made to see if it matches an artifact id. If it does, we retrieve its title and cross-reference and show it in a dedicated section "Matching Artifact". This section does not appear when the type of link "Is Parent of" is selected.
* The dropdown of the component must open "up" or "down" according to the space left on the viewport. It must allow people to navigate it with the keyboard. It must handle scrolling.

## Considered Options

* Select2 with modifications
* List-picker with modifications
* Dedicated "Auto-completer" component

## Decision Outcome

Chosen option: "Dedicated 'Auto-completer' component", because it comes out best (see below).

## Pros and Cons of the Options

### Select2 with modifications

The interactive mock-up for this epic used [Select2][2]. It also "hacked" it to handle a special option for "Create a new artifact…".

* Good, because Select2 already exists and its look-and-feel is very close to the mock-up component.
* Good, because the mock-up found and solved the main difficulties.
* Bad, because mock-up code does not equal production-grade code. We don't want to rely on "hacks" in production code if we can avoid it.
* Bad, because Select2 was not designed to handle groups of options with different behaviours. It expects all options to behave the same way.
* Bad, because we want to reduce the usage of Select2. Select2 is quite hard to use, we have spent a long time battling with its API and documentation, for example in Open-list fields in the Artifact Modal. Any deviation from the mock-up will be painful to implement.
* Bad, because Select2 depends on [jQuery][3]. jQuery was written to make it simpler to use the DOM, but since then, the DOM has improved massively. It is now easy to replace most of jQuery's calls by native DOM calls. Furthermore, Tuleap provides two versions of jQuery, both of which are no longer supported. We want to reduce and eventually remove all dependencies to either of those versions.

### List-picker with modifications

The auto-completer field looks a lot like [List-picker][4]. However, List-picker does not handle "special options" like "Create a new artifact…". It will require modifications.

* Good, because List-picker already exists and already handles keyboard navigation, scrolling, etc.
* Good, because List-picker was designed to replace Select2.
* Bad, because List-picker was not designed to wait for an asynchronous list of options. It was designed to "extend" a `<select>` tag and to react when its options change. It was not designed to render a "waiting" state nor an "error" state.
* Bad, because List-picker was not designed to handle groups of options that are fetched differently, and with different behaviours. It expects all options to behave the same way.
* Bad, because it was not designed to handle special cases like the "Create a new artifact…" and the "Link to {id}" options.
* Bad, because for each of the above, we will need to "hack" a workaround for those cases, or to modify the library.

### Dedicated "Auto-completer" component

We write a dedicated component and library for the auto-completer component. We fork List-picker and remove what is not needed.

* Good, because since it is dedicated, it can handle all the constraints listed in Decision Drivers.
* Good, because it leaves List-picker untouched. We don't add more options and hacks to it. It does the job it was made for.
* Bad, because a lot of code must be written. We must take care to also handle keyboard navigation, scrolling, etc.

[1]: https://tuleap.net/plugins/tracker/?aid=24969
[2]: https://select2.org/
[3]: https://jquery.com/
[4]: <../../list-picker/package.json>
[5]: ./0002-lazybox.md
