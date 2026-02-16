# Drag autoscroll

This lib implements automatic viewport scrolling when a dragged item approaches the top or bottom edge of the screen.
The scroll speed increases progressively as the cursor gets closer to the viewport edge, providing smooth and intuitive
navigation during drag operations.

## Implementation

If the drag and drop is based on the draggable API (like `@tuleap/drag-and-drop` does), use `useDragAutoscrollWithDraggableEvents()`.

ex:

```TS
const drag_autoscroll = useDragAutoscrollWithDraggableEvents();

this.drek = init({
    // [...]
    onDragStart: (): void => {
        drag_autoscroll.start();
    },
    cleanupAfterDragCallback: (): void => {
       drag_autoscroll.stop();
    },
});
```

Else use `useDragAutoscrollWithPointerEvents()`.

ex:

```TS
const drag_autoscroll = useDragAutoscrollWithPointerEvents();

const handleDragStart = (event: PointerEvent): void => {
    drag_autoscroll.start();
}

const handleDragCancelled = (event: PointerEvent): void => {
    drag_autoscroll.stop();
}

const handleDrop = (event: PointerEvent): void => {
    drag_autoscroll.stop();
}
```
