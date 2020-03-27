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
    AfterDropEventSource,
    AfterDropListener,
    DragStartContext,
    DrekkenovInitOptions,
} from "./types";
import { HIDE_CSS_CLASS } from "./constants";

export class OngoingDrag implements AfterDropListener {
    readonly dragged_element: HTMLElement;
    readonly source_dropzone: HTMLElement;
    readonly initial_sibling: Element | null;

    constructor(event_source: AfterDropEventSource, drag_start_context: DragStartContext) {
        this.dragged_element = drag_start_context.dragged_element;
        this.source_dropzone = drag_start_context.source_dropzone;
        this.initial_sibling = drag_start_context.initial_sibling;
        this.pinSourceDropzoneHeightToAvoidFlickerOverCollapsedColumns();
        event_source.attachAfterDropListener(this);
    }

    public hideDraggedElement(options: DrekkenovInitOptions): void {
        if (this.dragged_element.parentNode !== options.mirror_container) {
            options.mirror_container.append(this.dragged_element);
            this.dragged_element.classList.add(HIDE_CSS_CLASS);
        }
    }

    private pinSourceDropzoneHeightToAvoidFlickerOverCollapsedColumns(): void {
        const { height } = this.source_dropzone.getBoundingClientRect();
        this.source_dropzone.style.height = height + "px";
    }

    public afterDrop(): void {
        // Restore the element at its original place, otherwise Vue is confused and swaps elements
        this.source_dropzone.insertBefore(this.dragged_element, this.initial_sibling);
        this.dragged_element.classList.remove(HIDE_CSS_CLASS);
        this.source_dropzone.style.height = "";
    }
}
