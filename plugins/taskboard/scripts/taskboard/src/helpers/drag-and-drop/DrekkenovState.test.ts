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

import {
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
jest.mock("./event-handler-factory");
jest.mock("./OngoingDrag", () => {
    return {
        OngoingDrag: jest.fn().mockImplementation(() => {
            return {} as OngoingDrag;
        }),
    };
});
jest.mock("./DropGhost");
jest.mock("./DocumentEventsHandler", () => {
    return {
        DocumentEventsHandler: jest.fn().mockImplementation(() => {
            return ({
                attachDragDropListeners: jest.fn(),
            } as unknown) as DocumentEventsHandler;
        }),
    };
});

describe(`DrekkenovState`, () => {
    let state: DrekkenovState;
    let options: DrekkenovInitOptions;
    let doc: Document;
    beforeEach(() => {
        doc = createLocalDocument();
        options = ({
            cleanupAfterDragCallback: jest.fn(),
        } as unknown) as DrekkenovInitOptions;
        state = new DrekkenovState(options, doc);
    });

    afterEach(() => {
        const ongoing_drag_constructor = (OngoingDrag as unknown) as jest.SpyInstance;
        ongoing_drag_constructor.mockClear();
        const document_constructor = (DocumentEventsHandler as unknown) as jest.SpyInstance;
        document_constructor.mockClear();
    });

    describe(`dispatchAfterDropEvent()`, () => {
        it(`calls afterDrop() for each listener of the list
            and calls options' cleanupAfterDragCallback()
            and detaches all listeners`, () => {
            const fake_listener: AfterDropListener = {
                afterDrop: jest.fn(),
            };
            const other_fake_listener: AfterDropListener = {
                afterDrop: jest.fn(),
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
            const mock_handler: DragHandler = jest.fn();
            const dragStartHandlerFactory = jest
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
            jest.spyOn(DropGhost, "create").mockReturnValue(drop_ghost);
            const handlers = {} as DragDropHandlers;
            const handlersFactory = jest
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

            const ongoing_drag_constructor = (OngoingDrag as unknown) as jest.SpyInstance;
            const ongoing_drag = ongoing_drag_constructor.mock.results[0].value;
            expect(ongoing_drag_constructor).toHaveBeenCalledWith(state, drag_start_context);
            expect(DropGhost.create).toHaveBeenCalledWith(state, ongoing_drag);
            expect(handlersFactory).toHaveBeenCalledWith(options, state, ongoing_drag, drop_ghost);

            const document_constructor = (DocumentEventsHandler as unknown) as jest.SpyInstance;
            const document_event_handler = document_constructor.mock.results[0].value;
            expect(document_constructor).toHaveBeenCalledWith(state, handlers, doc);
            expect(document_event_handler.attachDragDropListeners).toHaveBeenCalled();
        });
    });

    describe(`cleanup()`, () => {
        it(`dispatches afterDrop event`, () => {
            jest.spyOn(state, "dispatchAfterDropEvent");

            state.cleanup();

            expect(state.dispatchAfterDropEvent).toHaveBeenCalled();
        });
    });
});

function createLocalDocument(): Document {
    return document.implementation.createHTMLDocument();
}
