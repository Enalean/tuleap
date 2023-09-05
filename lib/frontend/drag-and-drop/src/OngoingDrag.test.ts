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

import { describe, expect, it, beforeEach, vi } from "vitest";
import type { AfterDropEventSource, DragStartContext, DrekkenovInitOptions } from "./types";
import { OngoingDrag } from "./OngoingDrag";
import { HIDE_CSS_CLASS } from "./constants";

describe(`OngoingDrag`, () => {
    let mock_event_source: AfterDropEventSource;

    beforeEach(() => {
        mock_event_source = {
            attachAfterDropListener: vi.fn(),
            dispatchAfterDropEvent: vi.fn(),
        };
    });

    describe(`constructor()`, () => {
        it(`assigns properties from DragStartContext
            and attaches itself to the AfterDropEventSource parameter
            and pins the source dropzone's height
            to avoid having a flickering height due to flex when users drag over collapsed columns`, () => {
            const doc = createLocalDocument();
            const drag_start_context = createDragStartContext(doc, true);
            const rect = { height: 100 } as DOMRect;
            vi.spyOn(drag_start_context.source_dropzone, "getBoundingClientRect").mockReturnValue(
                rect,
            );
            const ongoing_drag = new OngoingDrag(mock_event_source, drag_start_context);

            expect(ongoing_drag.dragged_element).toBe(drag_start_context.dragged_element);
            expect(ongoing_drag.source_dropzone).toBe(drag_start_context.source_dropzone);
            expect(ongoing_drag.initial_sibling).toBe(drag_start_context.initial_sibling);
            expect(mock_event_source.attachAfterDropListener).toHaveBeenCalledWith(ongoing_drag);
            expect(ongoing_drag.source_dropzone.style.height).toBe("100px");
        });
    });

    describe(`hideDraggedElement()`, () => {
        let mirror_container: Element;
        let options: DrekkenovInitOptions;
        let ongoing_drag: OngoingDrag;

        beforeEach(() => {
            const doc = createLocalDocument();
            mirror_container = doc.createElement("div");
            const drag_start_context = createDragStartContext(doc, true);
            ongoing_drag = new OngoingDrag(mock_event_source, drag_start_context);
            options = {
                mirror_container,
            } as unknown as DrekkenovInitOptions;
        });

        it(`when the dragged element is not already hidden,
            it will move it to the options' mirror_container
            and set the HIDE css class`, () => {
            ongoing_drag.hideDraggedElement(options);

            expect(ongoing_drag.dragged_element.parentElement).toBe(mirror_container);
            expect(ongoing_drag.dragged_element.classList.contains(HIDE_CSS_CLASS)).toBe(true);
        });

        it(`will not touch the dragged element's existing CSS classes`, () => {
            ongoing_drag.dragged_element.classList.add("custom-css-class");

            ongoing_drag.hideDraggedElement(options);

            expect(ongoing_drag.dragged_element.classList.contains("custom-css-class")).toBe(true);
        });

        it(`when the dragged element is already child of mirror_container,
            it does nothing`, () => {
            mirror_container.append(ongoing_drag.dragged_element);

            ongoing_drag.hideDraggedElement(options);

            expect(ongoing_drag.dragged_element.classList.contains(HIDE_CSS_CLASS)).toBe(false);
        });
    });

    describe(`afterDrop()`, () => {
        let ongoing_drag: OngoingDrag;

        beforeEach(() => {
            const doc = createLocalDocument();
            const mirror_container = doc.createElement("div");
            const drag_start_context = createDragStartContext(doc, true);
            mirror_container.append(drag_start_context.dragged_element);
            drag_start_context.dragged_element.classList.add(HIDE_CSS_CLASS);

            ongoing_drag = new OngoingDrag(mock_event_source, drag_start_context);
        });

        it(`restores the dragged element at its original place to not confuse Vue
            and removes the HIDE css class`, () => {
            ongoing_drag.afterDrop();

            expect(ongoing_drag.dragged_element.parentElement).toBe(ongoing_drag.source_dropzone);
            expect(ongoing_drag.dragged_element.nextElementSibling).toBe(
                ongoing_drag.initial_sibling,
            );
            expect(ongoing_drag.dragged_element.classList.contains(HIDE_CSS_CLASS)).toBe(false);
        });

        it(`will not remove the dragged element's other CSS classes`, () => {
            ongoing_drag.dragged_element.classList.add("custom-css-class");

            ongoing_drag.afterDrop();

            expect(ongoing_drag.dragged_element.classList.contains("custom-css-class")).toBe(true);
        });

        it(`restores the source dropzone's height`, () => {
            ongoing_drag.source_dropzone.style.height = "100px";

            ongoing_drag.afterDrop();

            expect(ongoing_drag.source_dropzone.style.height).toBe("");
        });
    });
});

function createLocalDocument(): Document {
    return document.implementation.createHTMLDocument();
}

function createDragStartContext(doc: Document, has_sibling: boolean): DragStartContext {
    const dragged_element = doc.createElement("div");
    const source_dropzone = doc.createElement("div");
    source_dropzone.append(dragged_element);
    let initial_sibling = null;
    if (has_sibling) {
        initial_sibling = doc.createElement("div");
        source_dropzone.append(initial_sibling);
    }
    return {
        initial_sibling,
        source_dropzone,
        dragged_element,
    };
}
