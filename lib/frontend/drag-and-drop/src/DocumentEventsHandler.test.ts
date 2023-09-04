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
import { DocumentEventsHandler } from "./DocumentEventsHandler";
import type { AfterDropEventSource, DragDropHandlers } from "./types";

describe(`DocumentEventsHandler`, () => {
    let mock_event_source: AfterDropEventSource;
    let handlers: DragDropHandlers;
    let doc: Document;
    let events_handler: DocumentEventsHandler;

    beforeEach(() => {
        mock_event_source = {
            attachAfterDropListener: vi.fn(),
            dispatchAfterDropEvent: vi.fn(),
        };
        handlers = {
            dragOverHandler: vi.fn(),
            dropHandler: vi.fn(),
            dragEnterHandler: vi.fn(),
            dragEndHandler: vi.fn(),
            dragLeaveHandler: vi.fn(),
        };
        doc = createLocalDocument();
        events_handler = new DocumentEventsHandler(mock_event_source, handlers, doc);
    });

    describe(`constructor()`, () => {
        it(`attaches itself to the AfterDropEventSource parameter`, () => {
            expect(mock_event_source.attachAfterDropListener).toHaveBeenCalledWith(events_handler);
        });
    });

    describe(`attachDragDropListeners()`, () => {
        it(`attaches all handlers to drag/drop events only when drag has begun`, () => {
            vi.spyOn(doc, "addEventListener");
            events_handler.attachDragDropListeners();

            expect(doc.addEventListener).toHaveBeenCalledWith(
                "dragenter",
                handlers.dragEnterHandler,
            );
            expect(doc.addEventListener).toHaveBeenCalledWith(
                "dragleave",
                handlers.dragLeaveHandler,
            );
            expect(doc.addEventListener).toHaveBeenCalledWith("dragover", handlers.dragOverHandler);
            expect(doc.addEventListener).toHaveBeenCalledWith("dragend", handlers.dragEndHandler);
            expect(doc.addEventListener).toHaveBeenCalledWith("drop", handlers.dropHandler);
        });
    });

    describe(`afterDrop()`, () => {
        it(`detaches all handlers from drag/drop events`, () => {
            vi.spyOn(doc, "removeEventListener");
            events_handler.afterDrop();

            expect(doc.removeEventListener).toHaveBeenCalledWith(
                "dragenter",
                handlers.dragEnterHandler,
            );
            expect(doc.removeEventListener).toHaveBeenCalledWith(
                "dragleave",
                handlers.dragLeaveHandler,
            );
            expect(doc.removeEventListener).toHaveBeenCalledWith(
                "dragover",
                handlers.dragOverHandler,
            );
            expect(doc.removeEventListener).toHaveBeenCalledWith(
                "dragend",
                handlers.dragEndHandler,
            );
            expect(doc.removeEventListener).toHaveBeenCalledWith("drop", handlers.dropHandler);
        });
    });
});

function createLocalDocument(): Document {
    return document.implementation.createHTMLDocument();
}
