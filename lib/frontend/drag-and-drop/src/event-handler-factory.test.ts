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

import { describe, beforeEach, it, expect, vi } from "vitest";
import type { AfterDropEventSource, DragHandler, DrekkenovInitOptions } from "./types";
import type { DrekkenovState } from "./DrekkenovState";
import { dragStartFactory, handlersFactory } from "./event-handler-factory";
import * as dom_manipulation from "./dom-manipulation";
import type { OngoingDrag } from "./OngoingDrag";
import type { DropGhost } from "./DropGhost";
import { DREKKENOV_DRAG_DROP_TYPE } from "./constants";

describe(`event-handler-factory`, () => {
    let doc: Document;

    beforeEach(() => {
        doc = createLocalDocument();
    });

    describe(`dragStartFactory()`, () => {
        let options: DrekkenovInitOptions, state: DrekkenovState, dragStartHandler: DragHandler;

        beforeEach(() => {
            options = {
                isInvalidDragHandle: vi.fn(),
                isDropZone: vi.fn(),
                onDragStart: vi.fn(),
            } as unknown as DrekkenovInitOptions;
            state = {
                startDrag: vi.fn(),
            } as unknown as DrekkenovState;
            dragStartHandler = dragStartFactory(options, state);
        });

        it(`when the event has no dataTransfer, it does nothing`, () => {
            const event = { dataTransfer: null } as DragEvent;

            dragStartHandler(event);

            expect(state.startDrag).not.toHaveBeenCalled();
        });

        it(`when the event target is not a Node, it does nothing`, () => {
            const target = new EventTarget();
            const event = { dataTransfer: {}, target } as unknown as DragEvent;

            dragStartHandler(event);

            expect(state.startDrag).not.toHaveBeenCalled();
        });

        it(`when the event target is a dropzone,
            it does nothing because Dropzones cannot also be Draggables`, () => {
            const target = doc.createElement("div");
            vi.spyOn(options, "isDropZone").mockReturnValue(true);
            const event = { dataTransfer: {}, target } as unknown as DragEvent;

            dragStartHandler(event);

            expect(state.startDrag).not.toHaveBeenCalled();
        });

        it(`when the event target is an invalid drag handle,
            it prevents the default handler to forbid dragging
            so that links that are invalid handles won't be draggable`, () => {
            const target = doc.createElement("div");
            vi.spyOn(options, "isInvalidDragHandle").mockReturnValue(true);
            const event = {
                dataTransfer: {},
                target,
                preventDefault: vi.fn(),
            } as unknown as DragEvent;

            dragStartHandler(event);

            expect(state.startDrag).not.toHaveBeenCalled();
            expect(event.preventDefault).toHaveBeenCalled();
        });

        it(`when the event target has no draggable ancestor, it does nothing`, () => {
            const target = doc.createElement("div");
            vi.spyOn(dom_manipulation, "findClosestDraggable").mockReturnValue(null);
            const event = { dataTransfer: {}, target } as unknown as DragEvent;

            dragStartHandler(event);

            expect(state.startDrag).not.toHaveBeenCalled();
        });

        it(`when the event target has no dropzone ancestor, it does nothing`, () => {
            const target = doc.createElement("span");
            const draggable = doc.createElement("div");
            vi.spyOn(dom_manipulation, "findClosestDraggable").mockReturnValue(draggable);
            vi.spyOn(dom_manipulation, "findClosestDropzone").mockReturnValue(null);
            const event = { dataTransfer: {}, target } as unknown as DragEvent;

            dragStartHandler(event);

            expect(state.startDrag).not.toHaveBeenCalled();
        });

        it(`when all conditions are met,
            it will start the drag on DrekkenovState,
            and it will set the event's dataTransfer data for Firefox (which ignores it otherwise)
            and it will set the effectAllowed to "move" to show a nice cursor
            and it will call the onDragStart() callback from options`, () => {
            const target = doc.createElement("span");
            const draggable = doc.createElement("div");
            const source_dropzone = doc.createElement("div");
            const initial_sibling = doc.createElement("div");
            source_dropzone.append(draggable, initial_sibling);

            vi.spyOn(dom_manipulation, "findClosestDraggable").mockReturnValue(draggable);
            vi.spyOn(dom_manipulation, "findClosestDropzone").mockReturnValue(source_dropzone);
            const dataTransfer = {
                setData: vi.fn(),
                effectAllowed: "uninitialized",
            };
            const event = { dataTransfer, target } as unknown as DragEvent;

            dragStartHandler(event);

            expect(state.startDrag).toHaveBeenCalledWith({
                dragged_element: draggable,
                source_dropzone,
                initial_sibling,
            });
            expect(dataTransfer.effectAllowed).toBe("move");
            expect(dataTransfer.setData).toHaveBeenCalled();
            expect(options.onDragStart).toHaveBeenCalledWith({ dragged_element: draggable });
        });
    });

    describe(`dragEnterFactory()`, () => {
        let options: DrekkenovInitOptions,
            ongoing_drag: OngoingDrag,
            drop_ghost: DropGhost,
            dragEnterHandler: DragHandler;

        beforeEach(() => {
            options = {
                onDragEnter: vi.fn(),
                doesDropzoneAcceptDraggable: vi.fn(),
            } as unknown as DrekkenovInitOptions;
            const dragged_element = doc.createElement("div");
            const source_dropzone = doc.createElement("div");
            ongoing_drag = {
                dragged_element,
                source_dropzone,
            } as unknown as OngoingDrag;
            drop_ghost = {
                contains: vi.fn(),
                revertAtInitialPlace: vi.fn(),
                update: vi.fn(),
            } as unknown as DropGhost;
            const event_source = {} as AfterDropEventSource;
            dragEnterHandler = handlersFactory(
                options,
                event_source,
                ongoing_drag,
                drop_ghost,
            ).dragEnterHandler;
        });

        it(`when the event has no dataTransfer, it does nothing`, () => {
            const target = doc.createElement("div");
            const event = {
                target,
                dataTransfer: null,
                preventDefault: vi.fn(),
            } as unknown as DragEvent;

            dragEnterHandler(event);

            expect(options.onDragEnter).not.toHaveBeenCalled();
            expect(event.preventDefault).not.toHaveBeenCalled();
        });

        it(`when the event does not have the Drekkenov data type, it does nothing`, () => {
            const target = doc.createElement("div");
            const dataTransfer = { types: ["text/uri-list"] };
            const event = {
                target,
                dataTransfer,
                preventDefault: vi.fn(),
            } as unknown as DragEvent;

            dragEnterHandler(event);

            expect(options.onDragEnter).not.toHaveBeenCalled();
            expect(event.preventDefault).not.toHaveBeenCalled();
        });

        it(`when the event target is not a Node, it does nothing`, () => {
            const target = new EventTarget();
            const dataTransfer = { types: [DREKKENOV_DRAG_DROP_TYPE] };
            const event = {
                target,
                dataTransfer,
                preventDefault: vi.fn(),
            } as unknown as DragEvent;

            dragEnterHandler(event);

            expect(options.onDragEnter).not.toHaveBeenCalled();
            expect(event.preventDefault).not.toHaveBeenCalled();
        });

        it(`when the drop ghost contains the event target,
            it does nothing to avoid triggering too many events`, () => {
            const target = doc.createElement("span");
            const dataTransfer = { types: [DREKKENOV_DRAG_DROP_TYPE] };
            const event = {
                target,
                dataTransfer,
                preventDefault: vi.fn(),
            } as unknown as DragEvent;
            vi.spyOn(drop_ghost, "contains").mockReturnValue(true);

            dragEnterHandler(event);

            expect(options.onDragEnter).not.toHaveBeenCalled();
            expect(event.preventDefault).not.toHaveBeenCalled();
        });

        it(`when the event target has no dropzone ancestor,
            it will revert the drop ghost at its initial place
            to let the user know they are going to cancel the drop`, () => {
            const target = doc.createElement("div");
            const dataTransfer = { types: [DREKKENOV_DRAG_DROP_TYPE] };
            const event = {
                target,
                dataTransfer,
                preventDefault: vi.fn(),
            } as unknown as DragEvent;
            vi.spyOn(drop_ghost, "contains").mockReturnValue(false);
            vi.spyOn(dom_manipulation, "findClosestDropzone").mockReturnValue(null);

            dragEnterHandler(event);

            expect(drop_ghost.revertAtInitialPlace).toHaveBeenCalled();
            expect(options.onDragEnter).not.toHaveBeenCalled();
            expect(event.preventDefault).not.toHaveBeenCalled();
        });

        it(`when doesDropzoneAcceptDraggable() returns false,
            it will revert the drop ghost at its initial place
            to let the user know they are going to cancel the drop`, () => {
            const target = doc.createElement("div");
            const dataTransfer = { types: [DREKKENOV_DRAG_DROP_TYPE] };
            const event = {
                target,
                dataTransfer,
                preventDefault: vi.fn(),
            } as unknown as DragEvent;
            vi.spyOn(drop_ghost, "contains").mockReturnValue(false);
            const target_dropzone = doc.createElement("div");
            vi.spyOn(dom_manipulation, "findClosestDropzone").mockReturnValue(target_dropzone);
            vi.spyOn(options, "doesDropzoneAcceptDraggable").mockReturnValue(false);

            dragEnterHandler(event);

            expect(drop_ghost.revertAtInitialPlace).toHaveBeenCalled();
            expect(options.onDragEnter).not.toHaveBeenCalled();
            expect(event.preventDefault).not.toHaveBeenCalled();
        });

        it(`when the drop is valid,
            it will insert the drop ghost in the dropzone ancestor,
            and it will prevent the event's default handler to let the browser know the drop is possible
            and it will call the onDragEnter() callback from options`, () => {
            const target = doc.createElement("div");
            const dataTransfer = { types: [DREKKENOV_DRAG_DROP_TYPE] };
            const event = {
                target,
                dataTransfer,
                clientY: 200,
                preventDefault: vi.fn(),
            } as unknown as DragEvent;
            vi.spyOn(drop_ghost, "contains").mockReturnValue(false);
            const target_dropzone = doc.createElement("div");
            vi.spyOn(dom_manipulation, "findClosestDropzone").mockReturnValue(target_dropzone);
            vi.spyOn(options, "doesDropzoneAcceptDraggable").mockReturnValue(true);

            dragEnterHandler(event);

            expect(event.preventDefault).toHaveBeenCalled();
            expect(drop_ghost.update).toHaveBeenCalledWith(target_dropzone, options, 200);
            expect(options.onDragEnter).toHaveBeenCalledWith({
                dragged_element: ongoing_drag.dragged_element,
                source_dropzone: ongoing_drag.source_dropzone,
                target_dropzone,
            });
        });
    });

    describe(`dragLeaveFactory()`, () => {
        let options: DrekkenovInitOptions,
            ongoing_drag: OngoingDrag,
            drop_ghost: DropGhost,
            dragLeaveHandler: DragHandler;

        beforeEach(() => {
            options = {
                onDragLeave: vi.fn(),
            } as unknown as DrekkenovInitOptions;
            const dragged_element = doc.createElement("div");
            ongoing_drag = { dragged_element } as unknown as OngoingDrag;
            const event_source = {} as AfterDropEventSource;
            drop_ghost = {
                contains: vi.fn(),
            } as unknown as DropGhost;
            dragLeaveHandler = handlersFactory(
                options,
                event_source,
                ongoing_drag,
                drop_ghost,
            ).dragLeaveHandler;
        });

        it(`when the event target is not a Node, it does nothing`, () => {
            const target = new EventTarget();
            const event = { target } as unknown as DragEvent;

            dragLeaveHandler(event);

            expect(options.onDragLeave).not.toHaveBeenCalled();
        });

        it(`when the drop ghost contains the event target,
            it does nothing to avoid triggering too many events`, () => {
            const target = doc.createElement("div");
            const event = { target } as unknown as DragEvent;
            vi.spyOn(drop_ghost, "contains").mockReturnValue(true);

            dragLeaveHandler(event);

            expect(options.onDragLeave).not.toHaveBeenCalled();
        });

        it(`when the event target has no dropzone ancestor, it does nothing`, () => {
            const target = doc.createElement("div");
            const event = { target } as unknown as DragEvent;
            vi.spyOn(drop_ghost, "contains").mockReturnValue(false);
            vi.spyOn(dom_manipulation, "findClosestDropzone").mockReturnValue(null);

            dragLeaveHandler(event);

            expect(options.onDragLeave).not.toHaveBeenCalled();
        });

        it(`when all conditions are met, it will call the onDragLeave() callback from options`, () => {
            const target = doc.createElement("div");
            const event = { target } as unknown as DragEvent;
            vi.spyOn(drop_ghost, "contains").mockReturnValue(false);
            const target_dropzone = doc.createElement("div");
            vi.spyOn(dom_manipulation, "findClosestDropzone").mockReturnValue(target_dropzone);

            dragLeaveHandler(event);

            expect(options.onDragLeave).toHaveBeenCalledWith({
                dragged_element: ongoing_drag.dragged_element,
                target_dropzone,
            });
        });
    });

    describe(`dragOverFactory()`, () => {
        let options: DrekkenovInitOptions,
            ongoing_drag: OngoingDrag,
            drop_ghost: DropGhost,
            dragOverHandler: DragHandler;

        beforeEach(() => {
            options = {
                doesDropzoneAcceptDraggable: vi.fn(),
            } as unknown as DrekkenovInitOptions;

            const dragged_element = doc.createElement("div");
            const source_dropzone = doc.createElement("div");
            ongoing_drag = {
                dragged_element,
                source_dropzone,
            } as unknown as OngoingDrag;
            drop_ghost = {
                isAtDraggedElementInitialPlace: vi.fn(),
            } as unknown as DropGhost;
            const event_source = {} as AfterDropEventSource;
            dragOverHandler = handlersFactory(
                options,
                event_source,
                ongoing_drag,
                drop_ghost,
            ).dragOverHandler;
        });

        it(`when the event target is not a Node, it does nothing`, () => {
            const target = new EventTarget();
            const event = { target, preventDefault: vi.fn() } as unknown as DragEvent;

            dragOverHandler(event);

            expect(event.preventDefault).not.toHaveBeenCalled();
        });

        it(`when the event has no dataTransfer, it does nothing`, () => {
            const target = doc.createElement("div");
            const event = {
                target,
                dataTransfer: null,
                preventDefault: vi.fn(),
            } as unknown as DragEvent;

            dragOverHandler(event);

            expect(event.preventDefault).not.toHaveBeenCalled();
        });

        it(`when the event target has no dropzone ancestor, it does nothing`, () => {
            const target = doc.createElement("div");
            const event = {
                target,
                dataTransfer: {},
                preventDefault: vi.fn(),
            } as unknown as DragEvent;
            vi.spyOn(dom_manipulation, "findClosestDropzone").mockReturnValue(null);

            dragOverHandler(event);

            expect(event.preventDefault).not.toHaveBeenCalled();
        });

        it(`when doesDropzoneAcceptDraggable() returns false, it does nothing`, () => {
            const target = doc.createElement("div");
            const event = {
                target,
                dataTransfer: {},
                preventDefault: vi.fn(),
            } as unknown as DragEvent;
            const target_dropzone = doc.createElement("div");
            vi.spyOn(dom_manipulation, "findClosestDropzone").mockReturnValue(target_dropzone);
            vi.spyOn(options, "doesDropzoneAcceptDraggable").mockReturnValue(false);

            dragOverHandler(event);

            expect(event.preventDefault).not.toHaveBeenCalled();
        });

        it(`when the drop ghost is at dragged element's initial place (same source dropzone, same sibling),
            it does nothing`, () => {
            const target = doc.createElement("div");
            const event = {
                target,
                dataTransfer: {},
                preventDefault: vi.fn(),
            } as unknown as DragEvent;
            const target_dropzone = doc.createElement("div");
            vi.spyOn(dom_manipulation, "findClosestDropzone").mockReturnValue(target_dropzone);
            vi.spyOn(options, "doesDropzoneAcceptDraggable").mockReturnValue(true);
            vi.spyOn(drop_ghost, "isAtDraggedElementInitialPlace").mockReturnValue(true);

            dragOverHandler(event);

            expect(event.preventDefault).not.toHaveBeenCalled();
        });

        it(`when the drop is valid,
            it will prevent the event's default handler to let the browser know the drop is possible
            and it will set the dropEffect to "move" to have a nice cursor`, () => {
            const target = doc.createElement("div");
            const dataTransfer = { dropEffect: "uninitialized " };
            const event = {
                target,
                dataTransfer,
                preventDefault: vi.fn(),
            } as unknown as DragEvent;
            const target_dropzone = doc.createElement("div");
            vi.spyOn(dom_manipulation, "findClosestDropzone").mockReturnValue(target_dropzone);
            vi.spyOn(options, "doesDropzoneAcceptDraggable").mockReturnValue(true);
            vi.spyOn(drop_ghost, "isAtDraggedElementInitialPlace").mockReturnValue(false);

            dragOverHandler(event);

            expect(event.preventDefault).toHaveBeenCalled();
            expect(dataTransfer.dropEffect).toBe("move");
        });
    });

    describe(`dropFactory()`, () => {
        let options: DrekkenovInitOptions,
            after_drop_dispatcher: AfterDropEventSource,
            ongoing_drag: OngoingDrag,
            drop_ghost: DropGhost,
            dropHandler: DragHandler;

        beforeEach(() => {
            options = {
                onDrop: vi.fn(),
            } as unknown as DrekkenovInitOptions;
            after_drop_dispatcher = {
                dispatchAfterDropEvent: vi.fn(),
            } as unknown as AfterDropEventSource;
            const dragged_element = doc.createElement("div");
            const source_dropzone = doc.createElement("div");
            ongoing_drag = {
                dragged_element,
                source_dropzone,
            } as unknown as OngoingDrag;
            const next_sibling = doc.createElement("div");
            drop_ghost = {
                getSibling(): Element | null {
                    return next_sibling;
                },
            } as DropGhost;
            dropHandler = handlersFactory(
                options,
                after_drop_dispatcher,
                ongoing_drag,
                drop_ghost,
            ).dropHandler;
        });

        it(`when the event target is not a Node, it does nothing`, () => {
            const target = new EventTarget();
            const event = { target, preventDefault: vi.fn() } as unknown as DragEvent;

            dropHandler(event);

            expect(event.preventDefault).not.toHaveBeenCalled();
        });

        it(`when the event has no dataTransfer, it does nothing`, () => {
            const target = doc.createElement("div");
            const event = {
                target,
                dataTransfer: null,
                preventDefault: vi.fn(),
            } as unknown as DragEvent;

            dropHandler(event);

            expect(event.preventDefault).not.toHaveBeenCalled();
        });

        it(`when the event does not have the Drekkenov data type, it does nothing`, () => {
            const target = doc.createElement("div");
            const dataTransfer = { types: ["text/uri-list"] };
            const event = {
                target,
                dataTransfer,
                preventDefault: vi.fn(),
            } as unknown as DragEvent;

            dropHandler(event);

            expect(event.preventDefault).not.toHaveBeenCalled();
        });

        it(`when the event target has no dropzone ancestor, it does nothing`, () => {
            const target = doc.createElement("div");
            const dataTransfer = { types: [DREKKENOV_DRAG_DROP_TYPE] };
            const event = {
                target,
                dataTransfer,
                preventDefault: vi.fn(),
            } as unknown as DragEvent;
            vi.spyOn(dom_manipulation, "findClosestDropzone").mockReturnValue(null);

            dropHandler(event);

            expect(event.preventDefault).not.toHaveBeenCalled();
        });

        it(`when the drop is valid,
            it will call the onDrop() callback from options
            and it will prevent the event's default handler to let the browser know the drop is valid
            and it will dispatch the AfterDrop event`, () => {
            const target = doc.createElement("div");
            const dataTransfer = { types: [DREKKENOV_DRAG_DROP_TYPE] };
            const event = {
                target,
                dataTransfer,
                preventDefault: vi.fn(),
            } as unknown as DragEvent;
            const target_dropzone = doc.createElement("div");
            vi.spyOn(dom_manipulation, "findClosestDropzone").mockReturnValue(target_dropzone);

            dropHandler(event);

            expect(options.onDrop).toHaveBeenCalledWith({
                dropped_element: ongoing_drag.dragged_element,
                next_sibling: drop_ghost.getSibling(),
                source_dropzone: ongoing_drag.source_dropzone,
                target_dropzone,
            });
            expect(event.preventDefault).toHaveBeenCalled();
            expect(after_drop_dispatcher.dispatchAfterDropEvent).toHaveBeenCalled();
        });
    });

    describe(`dragEndFactory()`, () => {
        let options: DrekkenovInitOptions,
            after_drop_dispatcher: AfterDropEventSource,
            dragEndHandler: DragHandler;

        beforeEach(() => {
            options = {
                isDraggable: vi.fn(),
            } as unknown as DrekkenovInitOptions;
            after_drop_dispatcher = {
                dispatchAfterDropEvent: vi.fn(),
            } as unknown as AfterDropEventSource;
            const ongoing_drag = {} as OngoingDrag;
            const drop_ghost = {} as DropGhost;
            dragEndHandler = handlersFactory(
                options,
                after_drop_dispatcher,
                ongoing_drag,
                drop_ghost,
            ).dragEndHandler;
        });

        it(`when the event target is not an HTMLElement, it does nothing`, () => {
            const target = new EventTarget();
            const event = { target } as DragEvent;

            dragEndHandler(event);

            expect(after_drop_dispatcher.dispatchAfterDropEvent).not.toHaveBeenCalled();
        });

        it(`when the event target is not draggable, it does nothing`, () => {
            const target = doc.createElement("div");
            const event = { target } as unknown as DragEvent;
            vi.spyOn(options, "isDraggable").mockReturnValue(false);

            dragEndHandler(event);

            expect(after_drop_dispatcher.dispatchAfterDropEvent).not.toHaveBeenCalled();
        });

        it(`when the event target is draggable, it will dispatch the AfterDrop event`, () => {
            const target = doc.createElement("div");
            const event = { target } as unknown as DragEvent;
            vi.spyOn(options, "isDraggable").mockReturnValue(true);

            dragEndHandler(event);

            expect(after_drop_dispatcher.dispatchAfterDropEvent).toHaveBeenCalled();
        });
    });
});

function createLocalDocument(): Document {
    return document.implementation.createHTMLDocument();
}
