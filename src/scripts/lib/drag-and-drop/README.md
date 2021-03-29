# Drag and Drop

## General concepts:

- add `draggable="true"` on elements that should be drag & dropped
- add `data-is-container="true"` on elements you to transform into a drop zone
- add `data-not-drag-handle="true"` on buttons and links inside the containers (to make them not draggable)
  - For elements others than links, you must add `draggable="true"` with `data-not-drag-handle="true"`

## Implementation:

The minimal implementation you can provide is the definition of which element can be draggable,
at this point you only will have the cursor indicating you that dragging the element is possible
```
    this.drek = init({
        mirror_container: <dom_element>,
        isDropZone: isContainer,
        isDraggable: canMove,
        isInvalidDragHandle: invalid,
        isConsideredInDropzone,
        doesDropzoneAcceptDraggable {
           // will be implemented later
        },
        onDrop: (): void => {
           // will be implemented later
        },
        cleanupAfterDragCallback: (): void => {
           // will be implemented later
        },
    });
```

Then you can choose which DOM containers will accept the drop, and add functional rules to prevent drop.
```
    this.drek = init({
        ...
        // Add functional rule to accept/reject drop
        doesDropzoneAcceptDraggable: (context: PossibleDropCallbackParameter): boolean => {
            return checkAcceptsDrop({
                dropped_card: context.dragged_element,
                source_cell: context.source_dropzone,
                target_cell: context.target_dropzone,
            });
        },
        // Clean the errors messages displayed by rejecting drop
        cleanupAfterDragCallback: (): void => {
            return checkAfterDrag();
        },
    });
```

Next you can implement what will occur on drop in `onDrop` method
```
    onDrop: (context: SuccessfulDropCallbackParameter): void => {
    }
```

You might have some more complex rules (like drop in closed column in taskboard),
then you should implement following methods:
`onDragStart`, `onDragEnter`, `onDragLeave`
