---
routeAlias: editor-view
layout: center
---

# 1 - EditorView

---

## 1 - EditorView - <NodePackage name="prosemirror-view" />

EditorView represents the UI of an editor.

This object is at the highest level of an editor.

It renders the document contained in its state into the root `HTMLElement`.

```ts
import { EditorView } from "prosemirror-view";

const editor: EditorView = new EditorView(root_html_element, { state });
```

---

## 1 - EditorView

It allows to (non-exhaustive list):
- access the DOM content of the editor through its `view.dom` property.
- retrieve the position of a Node in its State associated to a DOM node or coords -> `posAtDOM` \ `posAtCoords`.
- retrieve the DOM node associated to an element inside its state or coords from a position in the state -> `domAtPos` \ `coordsAtPos`.
- access the current `EditorState`
- dispatch updates inside the editor through its method `view.dispatch`.

⚠️ It is tempting to manipulate the DOM directly. However, the changes will be overwritten by ProseMirror during the next update.
