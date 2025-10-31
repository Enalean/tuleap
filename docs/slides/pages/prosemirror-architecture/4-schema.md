---
routeAlias: editor-schema
layout: center
---

# 4 - Schema

---

## 4 - Schema - <NodePackage name="prosemirror-schema-basic"/>

The schema of an editor defines how a document is built. It is accessible from the state and nodes through `state.schema` and `node.type.schema`.

It defines:
- Which types of nodes are allowed in the document.
- Which types of nodes are allowed in given types of nodes (ex: a list node only allows list-items nodes as direct children).
- Which marks are allowed in the document.
- Which marks (link, bold, emphasize, etc.) can be added to which node.
- Which attributes exists on nodes
- How is built a representation of a ProseMirror Node from a DOM node -> `toDOM`
- How is built a DOM node from a ProseMirror Node representation -> `parseDOM`

---

## 4 - Schema

Default schemas can be imported from `prosemirror-schema-basic` and `prosemirror-schema-list` and be customised following our needs.

Defining a schema consists in defining `NodeSpecs` and `MarkSpecs`.

Example with our `async_cross_reference` custom `Mark`:

```ts
const async_cross_reference_mark_spec: MarkSpec = {
    inclusive: false,
    spanning: false,
    attrs: {
        text: { validate: "string" },
        project_id: { validate: "number" },
    },
    toDOM(node): DOMOutputSpec {
        const { text, project_id } = node.attrs;

        // Return a Hybrids custom element whose tag is <async_cross_reference/>
        return createAsyncCrossReference(text, project_id);
    },
};
```

---

## 4 - Schema

Example with artidoc's mono-editor feature where the title and the description of a given section are managed in the same editor:
- The root of the document can only contain one node of type `artidoc-section`.
- The node `artidoc-section` only contains two nodes: one node `artidoc-section-title` & one node `artidoc-section-description`.
- The node `artidoc-section-title` disallows marks and contains only raw text.
- The node `artidoc-section-description` contains blocks (paragraphs, lists, blockquotes, etc.).

See artidoc-editor-schema.ts

---

## 4 - Schema

This schema allows us to build a ProseMirror Node from a DOM node using `DOMParser`:

```ts
import { DOMParser, type Node } from "prosemirror-model";

type EditorNode = Node; // Type alias to avoid confusion with DOM's Node interface

const doc: EditorNode = DOMParser.fromSchema(schema).parse(html_content);
```

And it also allows us to build a DOM node from a ProseMirror Node using `DOMSerializer`:

```ts
import { DOMSerializer } from "prosemirror-model";

const html: HTMLElement = DomSerializer.fromSchema(schema).serializeFragment(
    state.doc,
    { document },
    document.createElement("div"),
);
```

---

## 4 - Schema

Last but not least, it allows us to create Nodes:

```ts
const image_node = state.schema.nodes.image.create({ src: "avatar.png" });
const bold_heading_1 = state.schema.nodes.headings.create(
    { level: 1 },
    "This is a H1 title",
    [state.schema.marks.bold.create()]
);
```

But also Marks:

```ts
const link_mark = state.schema.marks.link.create({ href: "https://example.com" });
const subscript = state.schema.marks.subscript.create();
```

---

## 4 - Schema

In summary, the schema:
- Defines the nodes and marks of a document
- Defines the constraints for building a document
- Makes it possible to map DOM nodes to ProseMirror nodes
