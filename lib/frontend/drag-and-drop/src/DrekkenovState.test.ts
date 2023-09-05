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

import type { SpyInstance } from "vitest";
import { describe, it, beforeEach, afterEach, expect, vi } from "vitest";
import type {
    AfterDropListener,
    DragDropHandlers,
    DragHandler,
    DragStartContext,
    DrekkenovInitOptions,
} from "./types";
import { DrekkenovState } from "./DrekkenovState";
import * as handlers_factory_module from "./event-handler-factory";
import { OngoingDrag } from "./OngoingDrag";
import { DropGhost } from "./DropGhost";
import { DocumentEventsHandler } from "./DocumentEventsHandler";
vi.mock("./OngoingDrag", () => {
    return { OngoingDrag: vi.fn() };
});
vi.mock("./DropGhost", () => {
    const mocked_class = { create: vi.fn() };
    return { DropGhost: mocked_class };
});
vi.mock("./DocumentEventsHandler", () => {
    const mocked_class = vi.fn();
    mocked_class.prototype.attachDragDropListeners = vi.fn();
    return { DocumentEventsHandler: mocked_class };
});

describe(`DrekkenovState`, () => {
    let state: DrekkenovState;
    let options: DrekkenovInitOptions;
    let doc: Document;
    beforeEach(() => {
        doc = createLocalDocument();
        options = {
            cleanupAfterDragCallback: vi.fn(),
        } as unknown as DrekkenovInitOptions;
        state = new DrekkenovState(options, doc);
    });

    afterEach(() => {
        const ongoing_drag_constructor = OngoingDrag as unknown as SpyInstance;
        ongoing_drag_constructor.mockClear();
        const document_constructor = DocumentEventsHandler as unknown as SpyInstance;
        document_constructor.mockClear();
    });

    describe(`dispatchAfterDropEvent()`, () => {
        it(`calls afterDrop() for each listener of the list
            and calls options' cleanupAfterDragCallback()
            and detaches all listeners`, () => {
            const fake_listener: AfterDropListener = {
                afterDrop: vi.fn(),
            };
            const other_fake_listener: AfterDropListener = {
                afterDrop: vi.fn(),
            };

            state.attachAfterDropListener(fake_listener);
            state.attachAfterDropListener(other_fake_listener);

            state.dispatchAfterDropEvent();
            // Second dispatch should NOT trigger the listeners again
            state.dispatchAfterDropEvent();

            expect(fake_listener.afterDrop).toBeCalledTimes(1);
            expect(other_fake_listener.afterDrop).toHaveBeenCalledTimes(1);
            expect(options.cleanupAfterDragCallback).toHaveBeenCalled();
        });
    });

    describe(`createDragStartHandler()`, () => {
        it(`returns a dragstart handler initialized with options`, () => {
            const mock_handler: DragHandler = vi.fn();
            const dragStartHandlerFactory = vi
                .spyOn(handlers_factory_module, "dragStartFactory")
                .mockImplementation(() => mock_handler);

            const drag_start_handler = state.createDragStartHandler();

            expect(dragStartHandlerFactory).toHaveBeenCalledWith(options, state);
            expect(drag_start_handler).toBe(mock_handler);
        });
    });

    describe(`startDrag()`, () => {
        it(`creates an OngoingDrag, a DropGhost, drag handlers and a DocumentEventsHandler,
            and attaches drag/drop listeners on the document`, () => {
            const drop_ghost = {} as DropGhost;
            (DropGhost.create as unknown as SpyInstance).mockReturnValue(drop_ghost);
            const handlers = {} as DragDropHandlers;
            const handlersFactory = vi
                .spyOn(handlers_factory_module, "handlersFactory")
                .mockReturnValue(handlers);

            const dragged_element = doc.createElement("div");
            const source_dropzone = doc.createElement("div");
            const initial_sibling = doc.createElement("div");
            const drag_start_context: DragStartContext = {
                dragged_element,
                initial_sibling,
                source_dropzone,
            };

            state.startDrag(drag_start_context);

            const ongoing_drag_constructor = OngoingDrag as unknown as SpyInstance;

            expect(ongoing_drag_constructor).toHaveBeenCalledWith(state, drag_start_context);
            expect(DropGhost.create).toHaveBeenCalledWith(
                state,
                expect.any(ongoing_drag_constructor),
            );
            expect(handlersFactory).toHaveBeenCalledWith(
                options,
                state,
                expect.any(ongoing_drag_constructor),
                drop_ghost,
            );

            const document_constructor = DocumentEventsHandler as unknown as SpyInstance;
            expect(document_constructor).toHaveBeenCalledWith(state, handlers, doc);
            expect(DocumentEventsHandler.prototype.attachDragDropListeners).toHaveBeenCalled();
        });
    });

    describe(`cleanup()`, () => {
        it(`dispatches afterDrop event`, () => {
            vi.spyOn(state, "dispatchAfterDropEvent");

            state.cleanup();

            expect(state.dispatchAfterDropEvent).toHaveBeenCalled();
        });
    });
});

function createLocalDocument(): Document {
    return document.implementation.createHTMLDocument();
}
