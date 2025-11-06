---
routeAlias: plugin-system
layout: center
---

# 3 - Plugin system

---

## 3 - Plugin system <NodePackage name="prosemirror-state"/>

The plugin system allows to add features to an editor.

A plugin allows to react at any moment of the editor's lifecycle. For instance:
- When the view is updated
- When the state is updated
- When a user input is detected (clic, keyboard, copy/paste, etc.)
- Before updates are applied

The hooks generally receive the `EditorView` or the `EditorState`, allowing us to do what we need to do, when we need to do it.

---

## 3 - Plugin system - The PluginInput

When a `Transaction` is applied, it checks whether the document has been updated or not.

If true, it transforms the document inside the state into a DOM structure and execute a callback with it:

```ts
new Plugin({
    state: {
        init(): void {},
        apply(tr, plugin_state, old_editor_state, new_editor_state): void {
            if (tr.docChanged) {
                update_callback(serializer.serializeDOM(new_editor_state.doc.content));
            }
        },
    },
});
```

---

## 3 - Plugin system - The LinkPopoverPlugin

When a click is detected in the editor, it calls `LinkPopoverInserter` whose role is to detect whether the click occurred
on a `LinkElement`. If true, it either displays a `LinkPopover` or a `CrossReferenceLinkPopover`, depending on the type of
the current link.

```ts
new Plugin({
    props: {
        handleClick: (view: EditorView, position: number): boolean =>
            LinkPopoverInserter(
                // [...]
            ).insertPopover(position),
    },
});
```

---

## 3 - Plugin system - The CrossReferencesPlugin

The CrossReferencesPlugin performs different actions at different times:
1. When the editor has been initialized, it detects the cross-references and wraps them into `async-cross-reference` Marks.

```ts {3-8}{maxHeight:'300px'}
new Plugin({
    key: new PluginKey("CrossReferencesPlugin"),
    view(view: EditorView): PluginView {
        view.dispatch(
            AllCrossReferencesLoader(view.state, project_id).loadAllCrossReferences(),
        );
        return {};
    },
    appendTransaction: (transactions, old_state): Transaction | null => {
        const updated_cross_reference =
            UpdatedCrossReferenceInTransactionFinder().find(transactions);
        if (!updated_cross_reference) {
            return null;
        }

        return UpdatedCrossReferenceHandler(
            MarkExtentsRetriever(EditorNodeAtPositionFinder(old_state)),
            project_id,
        ).handle(old_state, updated_cross_reference);
    },
    props: {
        transformPasted: (slice, view): Slice =>
            PastedReferencesTransformer(
                TextNodeWithReferencesSplitter(view.state.schema, project_id),
            ).transformPastedCrossReferencesToMark(slice),
    },
});
```

---

## 3 - Plugin system - The CrossReferencesPlugin

2. When an `updated-cross-reference` is found, it replaces the Mark wrapping the cross-reference with a new one containing the updated data.

```ts {9-20}{maxHeight:'300px'}
new Plugin({
    key: new PluginKey("CrossReferencesPlugin"),
    view(view: EditorView): PluginView {
        view.dispatch(
            AllCrossReferencesLoader(view.state, project_id).loadAllCrossReferences(),
        );
        return {};
    },
    appendTransaction: (transactions, old_state): Transaction | null => {
        const updated_cross_reference =
            UpdatedCrossReferenceInTransactionFinder().find(transactions);
        if (!updated_cross_reference) {
            return null;
        }

        return UpdatedCrossReferenceHandler(
            MarkExtentsRetriever(EditorNodeAtPositionFinder(old_state)),
            project_id,
        ).handle(old_state, updated_cross_reference);
    },
    props: {
        transformPasted: (slice, view): Slice =>
            PastedReferencesTransformer(
                TextNodeWithReferencesSplitter(view.state.schema, project_id),
            ).transformPastedCrossReferencesToMark(slice),
    },
});
```

---

## 3 - Plugin system - The CrossReferencesPlugin

3. When some content is pasted into the editor, it looks for cross-references inside it and adds `async-cross-reference`
Marks at the right place.

```ts {21-26}{maxHeight:'300px'}
new Plugin({
    key: new PluginKey("CrossReferencesPlugin"),
    view(view: EditorView): PluginView {
        view.dispatch(
            AllCrossReferencesLoader(view.state, project_id).loadAllCrossReferences(),
        );
        return {};
    },
    appendTransaction: (transactions, old_state): Transaction | null => {
        const updated_cross_reference =
            UpdatedCrossReferenceInTransactionFinder().find(transactions);
        if (!updated_cross_reference) {
            return null;
        }

        return UpdatedCrossReferenceHandler(
            MarkExtentsRetriever(EditorNodeAtPositionFinder(old_state)),
            project_id,
        ).handle(old_state, updated_cross_reference);
    },
    props: {
        transformPasted: (slice, view): Slice =>
            PastedReferencesTransformer(
                TextNodeWithReferencesSplitter(view.state.schema, project_id),
            ).transformPastedCrossReferencesToMark(slice),
    },
});
```

---

## 3 - Plugin system

In summary, plugins:
- extend the editors features.
- allow to trigger code from human interaction.
