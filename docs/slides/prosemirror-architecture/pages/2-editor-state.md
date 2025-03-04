---
routeAlias: editor-state
layout: center
---

# 2 - EditorState

---

## 2- EditorState - <NodePackage name="prosemirror-state"/>

It is contained into the `EditorView`: EditorView -> EditorState.

It knows the schema, the plugins and the content of the editor.

```ts
import { EditorState } from "prosemirror-state";

const doc = DOMParser.fromSchema(schema).parse(content);
const state = EditorState.create({
    doc,
    schema,
    plugins: [
        inputRules({
            rules: input_rules,
        }),
    ],
});
```

---

## 2- EditorState

It stores the current state of the editor and holds a representation of the editor content as a `Node` through its `state.doc` property.

⚠️ The type `Node` exported from ProseMirror can be confused with the type `Node` defined by the DOM api. These two types are totally different.

It allows to (non-exhaustive list):
- access the root ProseMirror `Node` of the current document.
- get the current `Selection` occurring inside the editor if any exists (a.k.a. which `Node` is selected).
- get access to the schema defining how the document looks like (see dedicated slides).
- create a `Transaction` (see dedicated slides).

---

## 2- EditorState

Basically:
- `EditorView` holds the HTML representation of the current document.
- `EditorState` holds a ProseMirror representation of the current document.

---

## 2- EditorState

An `EditorState` object is **immutable**.

Then how to proceed to bring changes to the document? <span v-click>Using a `Transaction`.</span>

<div v-click>
```ts
const new_transaction = state.tr; // Returns a brand new Transaction
```
</div>

---

## 2- EditorState

Writing a Transaction is like writing a kitchen recipe, with steps:

<div v-click>
```ts
new_transaction.setSelection(new TextSelection(from, to)); // Select text nodes between the { from, to } extents.

// Replace the current selection with an image node
new_transaction.replaceSelectionWith(
    state.schema.nodes.image.create({
        src: image.src,
        title: image.title,
        alt: image.title,
    }),
);
```

</div>

---

## 2- EditorState

Once the `Transaction` is setup, we have to dispatch it using `view.dispatch`:

```ts
view.dispatch(new_transaction);
```

And that's all.

<div v-click>
Note:
It is not always needed to dispatch the transaction by yourself. In certain contexts, you only need to return your Transaction
if there is any action to do or null otherwise.
</div>

<div v-click>
Example with an input rule:

```ts
const input_rule = new InputRule(
    regexp,
    (state, match, from, to): Transaction | null => {
        const transaction = state.tr;
        // [...]
        return transaction.addMark(from, to, state.schema.marks.bold);
    },
);
```
</div>

---

## 2- EditorState

When a Transaction is applied, a new EditorState is created from the steps defined into the Transaction and the EditorView is updated.

<img src="/img/data-flow.png" style="max-height: 95%" class="mx-auto"/>

---

## 2- EditorState

In summary, the EditorState:
- Is immutable.
- Holds the current state of the editor (nodes, selection, etc.).
- Allows to update the EditorView.
- Is rebuilt from transactions each time there are applied.
