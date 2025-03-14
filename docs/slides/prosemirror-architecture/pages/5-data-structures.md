---
routeAlias: data-structures
layout: center
---

# 5 - Data structures

---
layout: two-cols-header
---

## 5 - Data structures - ProseMirror Node

The main difference between a DOM node and a ProseMirror Node is the way it is modeled:

If we consider the following markup:

```html
<p>This is <strong>strong text with <em>emphasis</em></strong></p>
```

::left::

In HTML

<img src="/img/html-node.png" style="max-height: 95%"/>

The content is represented as a tree.

::right::

In ProseMirror

<img src="/img/prose-mirror-node.png" style="max-height: 95%"/>

The content is modeled as a flat sequence.

---
layout: default
---

## 5 - Data structures - ProseMirror Node

A ProseMirror `Node` is **readonly**. It allows to access its content, attributes, type and marks:

<img src="/img/node-instance.png" class="mx-auto"/>

Where the content is available as a `Fragment`

---

## 5 - Data structures - Fragment

A `Fragment` is **readonly**. It is a data structure representing a collection of child nodes.

It is a readonly object that allows to either navigate among its nodes using `.nodesBetween()`
```ts
// Iterate over all the nodes between the extents of the current selection to detect the presence of code blocks.
node.content.nodesBetween(selection.from, selection.to, (node) => {
    if (node.type === node.type.schema.nodes.code_block) {
        has_at_least_one_code_block = true;
    }
});
```

or `.descendants()`

```ts
// Iterate over all the children of a given node
state.doc.descendants((node) => {
    if (node.type === state.schema.nodes.artidoc_section_title) {
        artidoc_section_title_size = node.nodeSize;
        return false;
    }
});
```

---

## 5 - Data structures - Fragment

In summary:
- The content of an editor is represented as a flat sequence of `Node`.
- We can navigate through their children through a `Fragment`.
- Nodes are only mutable using transactions.
