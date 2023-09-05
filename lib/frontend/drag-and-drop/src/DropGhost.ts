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

import type { AfterDropEventSource, AfterDropListener, DrekkenovInitOptions } from "./types";
import { GHOST_CSS_CLASS, HIDE_CSS_CLASS } from "./constants";
import { cloneHTMLElement, findNextGhostSibling, insertAfter } from "./dom-manipulation";
import type { OngoingDrag } from "./OngoingDrag";

export class DropGhost implements AfterDropListener {
    constructor(
        event_source: AfterDropEventSource,
        private readonly ongoing_drag: OngoingDrag,
        private readonly element: Element,
    ) {
        event_source.attachAfterDropListener(this);
    }

    public static create(event_source: AfterDropEventSource, ongoing_drag: OngoingDrag): DropGhost {
        const clone = cloneHTMLElement(ongoing_drag.dragged_element);
        clone.classList.remove(HIDE_CSS_CLASS);
        clone.classList.add(GHOST_CSS_CLASS);
        return new DropGhost(event_source, ongoing_drag, clone);
    }

    public getSibling(): Element | null {
        return this.element.nextElementSibling;
    }

    public update(
        target_dropzone: Element,
        options: DrekkenovInitOptions,
        y_coordinate: number,
    ): void {
        window.requestAnimationFrame(() => {
            this.ongoing_drag.hideDraggedElement(options);
            const dropzone_children = Array.from(target_dropzone.children).filter(
                (child: Element): boolean => {
                    return child instanceof HTMLElement && options.isConsideredInDropzone(child);
                },
            );
            this.move(target_dropzone, dropzone_children, y_coordinate);
        });
    }

    public revertAtInitialPlace(): void {
        window.requestAnimationFrame(() => {
            if (!this.isAtDraggedElementInitialPlace()) {
                this.ongoing_drag.source_dropzone.insertBefore(
                    this.element,
                    this.ongoing_drag.initial_sibling,
                );
            }
        });
    }

    private move(
        target_dropzone: Element,
        dropzone_children: Element[],
        y_coordinate: number,
    ): void {
        if (dropzone_children.length === 0) {
            if (target_dropzone === this.element.parentElement) {
                return;
            }
            target_dropzone.prepend(this.element);
            return;
        }
        const next_ghost_sibling = findNextGhostSibling(y_coordinate, dropzone_children);
        if (next_ghost_sibling === null) {
            const last_child = dropzone_children[dropzone_children.length - 1];
            if (this.isUnchanged(last_child, this.element.previousElementSibling)) {
                return;
            }
            insertAfter(target_dropzone, this.element, last_child);
            return;
        }
        if (this.isUnchanged(next_ghost_sibling, this.element.nextElementSibling)) {
            return;
        }
        target_dropzone.insertBefore(this.element, next_ghost_sibling);
    }

    private isUnchanged(
        next_ghost_sibling: Element | null,
        current_ghost_sibling: Element | null,
    ): boolean {
        return next_ghost_sibling === current_ghost_sibling || next_ghost_sibling === this.element;
    }

    public isAtDraggedElementInitialPlace(): boolean {
        return (
            this.ongoing_drag.source_dropzone === this.element.parentElement &&
            this.ongoing_drag.initial_sibling === this.element.nextElementSibling
        );
    }

    public contains(node: Node): boolean {
        return this.element.contains(node);
    }

    public afterDrop(): void {
        this.element.remove();
    }
}
