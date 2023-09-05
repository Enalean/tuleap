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
    AfterDropListener,
    DragHandler,
    DragStartContext,
    DrekkenovInitOptions,
} from "./types";
import { OngoingDrag } from "./OngoingDrag";
import { DropGhost } from "./DropGhost";
import { DocumentEventsHandler } from "./DocumentEventsHandler";
import { dragStartFactory, handlersFactory } from "./event-handler-factory";

export class DrekkenovState implements AfterDropEventSource {
    private after_drop_listeners: AfterDropListener[] = [];

    constructor(
        private readonly options: DrekkenovInitOptions,
        private readonly document: Document,
    ) {}

    public attachAfterDropListener(listener: AfterDropListener): void {
        this.after_drop_listeners.push(listener);
    }

    private detachAllListeners(): void {
        this.after_drop_listeners = [];
    }

    public dispatchAfterDropEvent(): void {
        this.after_drop_listeners.forEach((listener) => {
            listener.afterDrop();
        });
        this.options.cleanupAfterDragCallback();
        this.detachAllListeners();
    }

    public createDragStartHandler(): DragHandler {
        return dragStartFactory(this.options, this);
    }

    public startDrag(drag_start_context: DragStartContext): void {
        const ongoing_drag = new OngoingDrag(this, drag_start_context);
        const drop_ghost = DropGhost.create(this, ongoing_drag);
        const handlers = handlersFactory(this.options, this, ongoing_drag, drop_ghost);
        const document_event_handler = new DocumentEventsHandler(this, handlers, this.document);
        document_event_handler.attachDragDropListeners();
    }

    public cleanup(): void {
        this.dispatchAfterDropEvent();
    }
}
