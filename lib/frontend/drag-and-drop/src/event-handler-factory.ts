/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type {
    AfterDropEventSource,
    DragDropHandlers,
    DragHandler,
    DragStartContext,
    DrekkenovInitOptions,
} from "./types";
import type { OngoingDrag } from "./OngoingDrag";
import { findClosestDraggable, findClosestDropzone } from "./dom-manipulation";
import type { DropGhost } from "./DropGhost";
import { DREKKENOV_DRAG_DROP_TYPE, DREKKENOV_DRAG_DROP_VALUE } from "./constants";
import type { DrekkenovState } from "./DrekkenovState";

export function dragStartFactory(
    options: DrekkenovInitOptions,
    state: DrekkenovState,
): DragHandler {
    return (event: DragEvent): void => {
        const element = event.target;
        if (
            event.dataTransfer === null ||
            !(element instanceof HTMLElement) ||
            options.isDropZone(element)
        ) {
            // Dropzones cannot also be Draggables
            return;
        }
        if (options.isInvalidDragHandle(element)) {
            // Prevent dragging invalid handles, even when they would be draggable (for example links)
            event.preventDefault();
            return;
        }

        const closest_draggable = findClosestDraggable(options, element);
        if (closest_draggable === null) {
            return;
        }
        const closest_dropzone = findClosestDropzone(options, element);
        if (closest_dropzone === null) {
            return;
        }
        const internal_context: DragStartContext = {
            dragged_element: closest_draggable,
            source_dropzone: closest_dropzone,
            initial_sibling: closest_draggable.nextElementSibling,
        };
        state.startDrag(internal_context);
        event.dataTransfer.setData(DREKKENOV_DRAG_DROP_TYPE, DREKKENOV_DRAG_DROP_VALUE);
        event.dataTransfer.effectAllowed = "move";

        const context = { dragged_element: closest_draggable };
        if (options.onDragStart) {
            options.onDragStart(context);
        }
    };
}

function dragEnterFactory(
    options: DrekkenovInitOptions,
    ongoing_drag: OngoingDrag,
    drop_ghost: DropGhost,
): DragHandler {
    return (event: DragEvent): void => {
        if (
            !(event.target instanceof Node) ||
            event.dataTransfer === null ||
            !event.dataTransfer.types.includes(DREKKENOV_DRAG_DROP_TYPE) ||
            drop_ghost.contains(event.target)
        ) {
            return;
        }
        const closest_dropzone = findClosestDropzone(options, event.target);
        if (closest_dropzone === null) {
            drop_ghost.revertAtInitialPlace();
            return;
        }
        const dragged_element = ongoing_drag.dragged_element;
        const context = {
            dragged_element,
            source_dropzone: ongoing_drag.source_dropzone,
            target_dropzone: closest_dropzone,
        };
        if (!options.doesDropzoneAcceptDraggable(context)) {
            drop_ghost.revertAtInitialPlace();
            return;
        }
        // Let the browser know the drop is possible
        event.preventDefault();

        drop_ghost.update(closest_dropzone, options, event.clientY);
        if (options.onDragEnter) {
            options.onDragEnter(context);
        }
    };
}

function dragLeaveFactory(
    options: DrekkenovInitOptions,
    ongoing_drag: OngoingDrag,
    drop_ghost: DropGhost,
): DragHandler {
    return (event: DragEvent): void => {
        if (!(event.target instanceof Node) || drop_ghost.contains(event.target)) {
            return;
        }
        const closest_dropzone = findClosestDropzone(options, event.target);
        if (closest_dropzone === null) {
            return;
        }
        const context = {
            dragged_element: ongoing_drag.dragged_element,
            target_dropzone: closest_dropzone,
        };

        if (options.onDragLeave) {
            options.onDragLeave(context);
        }
    };
}

function dragOverFactory(
    options: DrekkenovInitOptions,
    ongoing_drag: OngoingDrag,
    drop_ghost: DropGhost,
): DragHandler {
    return (event: DragEvent): void => {
        if (!(event.target instanceof Node) || event.dataTransfer === null) {
            return;
        }
        const closest_dropzone = findClosestDropzone(options, event.target);
        if (closest_dropzone === null) {
            return;
        }
        const context = {
            dragged_element: ongoing_drag.dragged_element,
            source_dropzone: ongoing_drag.source_dropzone,
            target_dropzone: closest_dropzone,
        };
        if (!options.doesDropzoneAcceptDraggable(context)) {
            return;
        }
        if (drop_ghost.isAtDraggedElementInitialPlace()) {
            return;
        }

        // Let the browser know the drop is possible
        event.preventDefault();
        event.dataTransfer.dropEffect = "move";
    };
}

function dropFactory(
    options: DrekkenovInitOptions,
    after_drop_dispatcher: AfterDropEventSource,
    ongoing_drag: OngoingDrag,
    drop_ghost: DropGhost,
): DragHandler {
    return (event: DragEvent): void => {
        if (
            !(event.target instanceof Node) ||
            event.dataTransfer === null ||
            !event.dataTransfer.types.includes(DREKKENOV_DRAG_DROP_TYPE)
        ) {
            return;
        }
        const closest_dropzone = findClosestDropzone(options, event.target);
        if (closest_dropzone === null) {
            return;
        }

        options.onDrop({
            dropped_element: ongoing_drag.dragged_element,
            next_sibling: drop_ghost.getSibling(),
            source_dropzone: ongoing_drag.source_dropzone,
            target_dropzone: closest_dropzone,
        });
        // Let the browser know the drop is successful
        event.preventDefault();
        // Sometimes, dragend is not called after drop...
        after_drop_dispatcher.dispatchAfterDropEvent();
    };
}

function dragEndFactory(
    options: DrekkenovInitOptions,
    after_drop_dispatcher: AfterDropEventSource,
): DragHandler {
    return (event: DragEvent): void => {
        const dropped_element = event.target;
        if (!(dropped_element instanceof HTMLElement) || !options.isDraggable(dropped_element)) {
            return;
        }

        after_drop_dispatcher.dispatchAfterDropEvent();
    };
}

export function handlersFactory(
    options: DrekkenovInitOptions,
    after_drop_event_source: AfterDropEventSource,
    ongoing_drag: OngoingDrag,
    drop_ghost: DropGhost,
): DragDropHandlers {
    return {
        dragEnterHandler: dragEnterFactory(options, ongoing_drag, drop_ghost),
        dragLeaveHandler: dragLeaveFactory(options, ongoing_drag, drop_ghost),
        dragOverHandler: dragOverFactory(options, ongoing_drag, drop_ghost),
        dropHandler: dropFactory(options, after_drop_event_source, ongoing_drag, drop_ghost),
        dragEndHandler: dragEndFactory(options, after_drop_event_source),
    };
}
