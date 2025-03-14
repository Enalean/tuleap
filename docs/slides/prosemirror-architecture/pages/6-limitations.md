---
routeAlias: limitations
layout: center
---

# 6 - Limitations

---

## 6 - Limitations

Because ProseMirror synchronizes its view and its state using transactions, it really doesn't like asynchronous code.

Example with the cross-references detection feature:

When a cross-reference `art #123` is found, we need to query the backend to:
1. Make sure the reference is valid
2. Retrieve the link to the referenced object

Then wrap `art #123` into something looking like a link.

<img src="/img/cross-reference.png" class="mx-auto"/>

---

## 6 - Limitations

If we wait for the backend to answer before dispatching a transaction, then we might end up with a `Mismatched transaction` error.

<img src="/img/range-error.png" class="mx-auto"/>

A `Mismatched transaction` error means that a transaction has been dispatched from a state that does not belong to the current view's state.

**In short**: ProseMirror tried to apply a transaction dispatched from an older state -> 💥

---

## 6 - Limitations

The strategy we implemented is the following:
1. Something looking like a cross-reference is detected (from an input rule, document parsing etc.).
2. We wrap it in a text node containing our custom `async-cross-reference` mark.
3. The `async-cross-reference` mark is rendered as a hybrids component.
4. The hybrids component queries the backend and updates its style accordingly to the received response.

With this strategy, the asynchronous code is managed outside the ProseMirror loop -> great success 🎉

---

## 6 - Limitations

In summary:
- ProseMirror is not compatible with asynchronous code because of its update mechanism.
- Asynchronous code has to be managed outside the ProseMirror loop.
