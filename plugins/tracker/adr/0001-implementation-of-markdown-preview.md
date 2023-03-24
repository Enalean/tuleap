# Triple implementation of Markdown Preview

* Status: accepted
* Deciders: Joris MASSON, Clarck ROBINSON
* Date: 2021-03-29

Technical Story: [story #18337][0] markdown replace text

## Context and Problem Statement

User can Preview the entered Markdown content in Text fields, Step Definition fields and Follow-up comments.
There are three different contexts:
1. Tracker Artifact view: FlamingParrot
2. TestManagement Step Definition field: Also FlamingParrot, but there is one format selectbox controlling
   two textarea fields. It is the only place in Tuleap with this pattern.
3. Artifact Modal v3: BurningParrot

FlamingParrot does not have access to `tlp` popovers library. BurningParrot does not have access
to Bootstrap 2 popovers library, so code cannot be easily shared between those contexts.

## Decision Drivers

* We have already refactored many things for this story: REST API representations, `codendi Rich Text Editor`,
  `CKEditor` image uploading libraries,  Tracker prototype frontend code...
* The Markdown epic effort has drifted way beyond expectations already.
* It is unclear whether The Markdown epic will continue after this story.

## Considered Options

* Have three implementations, one for each context
* Have two implementations: one that is shared for Artifact view and Artifact modal and another one
  for Step Definition fields
* Have a single implementation covering all contexts

## Decision Outcome

Chosen option: "Have three implementations", mainly because of the time criterion. Option Two has been marked as "Bonus"
tasks and could be attempted in Hackathon / spare time.

### Positive Consequences

* We were able to finish the story on time this release.

### Negative Consequences

* It creates technical debt on future maintainers. Change in Text fields will need three implementations. It is our hope
  that documenting this decision here will at least prevent the team from forgetting about it.
* It makes inconsistencies between text fields more likely. There is already one example: The "Syntax help" popover does
  not have the same look and feel between the Artifact View and the Artifact Modal.

## Pros and Cons of the Options

### Have three implementations

Step definition field has its own implementation in Vue (as it is built in Vue). Artifact Modal has its own implementation
too, also in Vue (the Text field and Follow-up components are in Vue inside AngularJS). Artifact view has its own
implementation, in TypeScript + [lit-html][1].

* Good, because it is faster to complete than the other two options.
* Good, because work can be split in the feature team.
* Bad, because it creates more technical debt on future maintainers. Change in Text fields will need three implementations.
* Bad, because it makes inconsistencies between text fields more likely.

### Have two implementations

Artifact view and Artifact modal have the same functional context (one selectbox per textarea), but different technical
stacks. This can be bridged if we can extract the TLP popovers library as an internal library and use it in both contexts.
However, the popovers library relies on TLP CSS colors which are not yet available as [CSS Custom Properties][2]. This
means we cannot build a library with a single CSS file output. We must either extract the colors as CSS Custom Properties
or publish a library with 12 CSS files, and import the right one.

* Good, because it creates less debt than "Have three implementations".
* Good, because we can reuse the TLP popovers library in other FlamingParrot / BurningParrot contexts.
* Bad, because the amount of work needed to extract the lib is unknown and hard to estimate.
* Bad, because it still leaves some technical debt to future maintainers.
* Bad, because it still makes inconsistencies between text fields more likely.

### Have a single implementation

We have developed a means to bind a text editor to an existing selectbox. It could be possible to create a selectbox for
the "Description" textarea of the TTM Step definition field and bind the "Expected results" textarea to the same selectbox.
We would also need to override the "Preview" button's behaviour, as it would have to await two promises and not just one.
Additionally, the Step Definition field is very complicated and offers other, potentially conflicting features such as
"Mark a step as deleted" and "Drag and drop of steps" to reorder them.

* Good, because it creates no technical debt.
* Good, because it makes inconsistencies unlikely (only one implementation).
* Good, because we can reuse the TLP popovers library in other FlamingParrot / BurningParrot contexts.
* Bad, because it complicates the `rich-text-editor` lib further by adding one more option to override the Preview button.
* Bad, because it is hard to estimate if (when ?) the other features of the field would conflict. It is likely we will
  only find out during implementation.
* Bad, because the amount of work needed to extract the lib is unknown and hard to estimate.

## Links

* [lit-html templating library][1]
* [CSS Custom Properties on MDN][2]

[0]: https://tuleap.net/plugins/tracker/?aid=18337
[1]: https://lit-html.polymer-project.org/
[2]: https://developer.mozilla.org/en-US/docs/Web/CSS/Using_CSS_custom_properties
