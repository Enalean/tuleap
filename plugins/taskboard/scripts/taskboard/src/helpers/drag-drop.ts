/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import { HandleDragPayload } from "../store/swimlane/type";
import { isDraggedOverAnotherSwimlane, isDraggedOverTheSourceCell } from "./html-to-item";
import { Store } from "vuex";
import { RootState } from "../store/type";

export function isContainer(element?: Element): boolean {
    if (!element || !(element instanceof HTMLElement)) {
        return false;
    }

    return Boolean(element.dataset.isContainer) === true;
}

export function canMove(element?: Element): boolean {
    if (!element || !(element instanceof HTMLElement)) {
        return false;
    }

    return Boolean(element.dataset.isDraggable) === true;
}

export function invalid(element?: Element, handle?: Element): boolean {
    if (!handle || !(handle instanceof HTMLElement)) {
        return true;
    }

    return Boolean(handle.closest("[data-not-drag-handle]"));
}

export function checkCellAcceptsDrop(store: Store<RootState>, payload: HandleDragPayload): boolean {
    if (
        !(payload.dropped_card instanceof HTMLElement) ||
        !(payload.target_cell instanceof HTMLElement) ||
        !(payload.source_cell instanceof HTMLElement)
    ) {
        store.commit("swimlane/unsetDropZoneRejectingDrop");

        return false;
    }

    if (isDraggedOverAnotherSwimlane(payload.target_cell, payload.source_cell)) {
        store.commit("swimlane/unsetDropZoneRejectingDrop");

        return false;
    }

    if (!isDropAcceptedInTarget(payload.dropped_card, payload.target_cell, payload.source_cell)) {
        store.commit("swimlane/setDropZoneRejectingDrop", payload.target_cell);

        return false;
    }

    store.commit("swimlane/unsetDropZoneRejectingDrop");

    return true;
}

function isDropAcceptedInTarget(
    dropped_card: HTMLElement,
    target_cell: HTMLElement,
    source_cell: HTMLElement
): boolean {
    if (isDraggedOverTheSourceCell(target_cell, source_cell)) {
        return true; // Allow reordering
    }

    const tracker_id: string | undefined = dropped_card.dataset.trackerId;
    const accepted_trackers_ids: string | undefined = target_cell.dataset.acceptedTrackersIds;

    if (!tracker_id || !accepted_trackers_ids) {
        return false;
    }

    return accepted_trackers_ids.split(",").includes(tracker_id);
}
