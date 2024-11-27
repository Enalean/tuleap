---
status: accepted
date: 2024-11-25
decision-makers: Thomas GORKA, Nicolas TERRAY, Marie Ange GARNIER
consulted: ""
informed: Thomas GERBET, Clarck ROBINSON, Kevin TRAINI, Manuel VACELET, Clarisse DESCHAMPS, Joris MASSON
---

# Management of Tuleap Cross-references in ProseMirror

## Context and Problem Statement

When the user has typed some text containing parts matching a Tuleap cross-reference format, or when the editor content
contains Tuleap cross-references, we need to:
1. Detect it.
2. Make sure the cross-reference points on an existing entity backend-side.
3. Display it as a link-like element.
4. Avoid tags related to cross-references to end up in the saved document.
   (e.g. no `<span class="cross-reference" data-href="...">art #123</span>` nor `<a class="cross-reference" href="...">art #123</span>`
   should be saved in the document).

Achieving this requires us to work asynchronously, which poses a number of problems:
- ProseMirror's Transactions are made to work synchronously. Although it is still possible to dispatch a Transaction from
  asynchronous code, it is not guaranteed that the EditorView will manage to apply it properly. Moreover, it makes the code
  complexity grow insanely high, and makes the feature quite unstable.

- We cannot keep track of the cross-references positions in the editor. We need a way to ensure that the rendered cross-references
  have always the right placement in the document, even if the user keeps modifying the document in the meantime.

- We need to be able to update asynchronously a cross-reference when it has been updated.

## Decision Drivers

* Be able to load and render cross-reference asynchronously with correct positioning.
* Be able to update the cross-references.
* Be able to debounce the API calls.
* Be able to save the cross-references as plain text.

## Considered Options

* Fix the curent implementation making use of [Decorations][1].
* Make use of Decorations combined with an [InputRule][2].
* Define a custom [Mark][3] combined with a [hybrids][4] custom element.

## Decision Outcome

Chosen option: Trash the current implementation and define a custom Mark combined with a hybrids custom element.

This solution has been tested during a POC demonstration, and has be found viable.

### Consequences

* Good, because we now have a stable feature.
* Bad, because we had to re-implement everything from scratch.

## Pros and Cons of the Options

### Fix the curent implementation making use of Decorations

A Decoration is something to be added exclusively in the editor's view, and that will never be serialized to DOM because
it does not make part of the editor's State. On the paper, it is convenient because it is easy to create, and it does not
mess up with the document content.

The current implementation has numerous design flaws making the cross-references detection and management unstable.
It is made through a plugin named "PluginTransformInput" whose role is to:
1. detect, load and render all the cross-references post editor initialization.
2. detect, load and render all the cross-references when the content of the editor is updated.
3. handle the update of cross-references (via the link-popover menu).

It works as follows:
1. It tests the RegExp `/\w+\s#[\w\-:./]+/` on what's returned by `view.doc.toString()`
   ⚠️ The `toString()` method of `view.doc` is flagged "for debugging purposes" and returns a ProseMirror style representation
      of the document tree, and not a textual/DOM representation of it.
2. If the RegExp matches, the content of `view.doc.toString()` is sent to `POST /api/v1/projects/:id/extract_references`
   so it can extract the cross-references from the document.
3. Once the REST route returned the collection of found cross-references, the plugin enters a computation phase.
4. For each cross-reference, it finds the editor nodes in which it appears (e.g: a paragraph, a blockquote, etc.) and records
   its positions (the cross-reference can exist in several places of the current document).
5. Then, for each found position, the plugin finds the position of the reference in its parent node's text in order to compute
   the future Decoration's extents (`{ from: number, to: number }`) using the context word the backend provides us. The context word
   is the word placed before the cross-reference. For instance, in the following text: "These are the steps to implement story #123",
   "implements" is the context word.
   ⚠️ The usage of this context word is rather wobbly because if the context word contains accentuated or special characters,
      it will be truncated by the backend. Therefore, the computation of the decoration's position can be potentially false.
      For instance, with the following text: "Ce document référence art #123", the context word is "rence".
      Moreover, if the context word appears several times in the text node, there is a risk of placement error. Even worse,
      no decoration at all will be created.
6. Once computed, the extents are used to create inline decorations that will be replaced in the plugin's internal state.

* Good, because it is already semi-working, and we could "just" fix it.
* Good, because cross-references detection in pasted content comes "for free".
* Good, because the decorations are recreated each time the editor updates. We do not have to explicitly update their positions.

* Bad, because fixing it will take a lot of time and efforts.
* Bad, because the decorations are recreated each time the editor updates.
  Note: We do not have the choice since we cannot update a decoration. We are supposed to destroy and rebuild them.
* Bad, because we have to parse the whole document, detect, query and extract references each time the editor updates and recreate the decorations.
* Bad, because the document updates at every keystroke/action, and because there is no debouncing: 50 keystrokes = 50 API calls/document parsing/decoration creations.
* Bad, because it is hard to compute the positions from a collection of extracted references.
* Bad, because it is unstable.

### Make use of Decorations combined with an InputRule

An InputRule is a rule that will apply a RegExp on the text that have been just typed in the editor (preceding the cursor).
If the RegExp matches, the handler of the InputRule will be executed. This handler receives the current EditorState, the result
of the RegExp and the extents of the matching text as parameters. It is handy because it means that we wouldn't have to
compute the positions, given that the InputRule provides it to us.

However, this solution would partially solve the problems of previous option:
- We cannot dispatch a Transaction from the InputRule handler because we have no access to the EditorView from it.
- We need to return a transaction with a special meta in order to be able to trigger the REST call, and the creation of
  the decoration (FTR: asynchronous code with Transactions is really not desirable).
- Because InputRules are triggered only when the user types text, existing cross-reference will not be extracted.

* Good, because we make an API call only when a cross-reference has been found by the InputRule.
* Good, because we don't need to perform crazy position computations to place the decoration properly.

* Bad, because it does not react when the user presses the [Enter] key.
* Bad, because it does not react when the user pastes content into the editor.
* Bad, because we can still end up having bad positioning if the API call takes too long to reply and the user enters text before the reference.
* Bad, because we still need to recreate a collection of Decorations each time the content of the editor is updated.
* Bad, because it only resolves a small part of the first option's problems.

### Define a custom Mark combined with a hybrids custom element.

Initially, Marks have been excluded from any solution consideration for the management of cross-references in ProseMirror,
because when the document content is serialized, Marks are serialized too. For the record, we do not want cross-references
tags to end up in the saved document, because it is considered as "special textual content" rather than a real element (unlike links).

Lets' consider this part taken from the [ProseMirror API reference][5]:

> A mark is a piece of information that can be attached to a node, such as it being emphasized, in code font, or a link.
  It has a type and optionally a set of attributes that provide further information (such as the target of the link).

A Mark is placed on a Node of the Editor, and is rendered as a DOM element that can have attributes.

Given that asynchronous code is not really compatible with ProseMirror's Transactions, what if:
- We define a custom Mark "async_cross_reference", having two attributes: text - the reference's text (ex: art #123) - and a project id.
- We define a custom element having the same tag as the custom mark.
- Make the mark create an instance of this custom element, providing it the reference's text and the current project id.
- Make the custom element deal with the API call.

It would mean that the only thing the editor would have to do is inserting a mark where there is potentially a cross-reference,
and let the mark do the rest in backstage.

To make it work, we can do all the following:
- Parse the document only once post-editor initialization to add the custom mark where we find cross-references.
- Define an InputRule whose role is to detect the parts of the text matching the Tuleap cross-reference format and append the custom mark to them.
- Listen for the `keypressed` event and insert our custom mark if the user pressed [Enter] after a cross-reference has been typed.
- Customize how the content is pasted into the editor to add the custom mark on textual nodes containing references.
- Customize the DOMSerializer to make it ignore our custom mark. This way it won't be serialized.

This solution is:
* Good, because it is better performance-wise given that we don't need to parse the document multiple times to extract the references.
* Good, because the asynchronous code is run outside the ProseMirror loop.
* Good, because we can debounce the API calls inside the custom element.
* Good, because the loading of all the references post editor initialization is way simpler than the former implementation.
* Good, because since marks are bound to an editor Node, we don't have to update their positions.
* Good, because we can retrieve a Mark at a given position to replace it if we need to.
* Good, because we can prevent the custom mark to be serialized, so it does not pollute the document.
* Good, because we can show feedback via the custom element (reference found/broken/loading), which is nice UX-wise.
* Good, because the overall solution is quite strong and stable.

* Bad, because we need to reimplement everything from the beginning.

## More Information

* [ProseMirror introduction](https://prosemirror.net/docs/guide/#intro)

[0](https://tuleap.net/plugins/tracker/?aid=40114)
[1](https://prosemirror.net/docs/ref/#view.Decorations)
[2](https://prosemirror.net/docs/ref/#inputrules)
[3](https://prosemirror.net/docs/ref/#model.Mark)
[4](https://hybrids.js.org/#/component-model/templates)
[5](https://prosemirror.net/docs/ref/)
