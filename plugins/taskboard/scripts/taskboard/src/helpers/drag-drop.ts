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
import { hasCardBeenDroppedInAnotherSwimlane } from "./html-to-item";
import { Store } from "vuex";
import { RootState } from "../store/type";

export function isContainer(element?: Element): boolean {
    if (!element) {
        return false;
    }
    return (
        element.classList.contains("taskboard-cell") &&
        !element.classList.contains("taskboard-cell-swimlane-header") &&
        !element.classList.contains("taskboard-card-parent") &&
        !element.classList.contains("taskboard-cell-collapsed") // Not supported yet
    );
}

export function canMove(element?: Element, target?: Element, handle?: Element): boolean {
    if (!element || !handle) {
        return false;
    }

    return (
        !element.classList.contains("taskboard-card-collapsed") &&
        (element.classList.contains("taskboard-cell-solo-card") ||
            element.classList.contains("taskboard-child"))
    );
}

export function invalid(element?: Element, handle?: Element): boolean {
    if (!handle) {
        return true;
    }

    return handle.classList.contains("taskboard-item-no-drag");
}

export function checkCellAcceptsDrop(store: Store<RootState>, payload: HandleDragPayload): boolean {
    if (
        !(payload.dropped_card instanceof HTMLElement) ||
        !(payload.target_cell instanceof HTMLElement) ||
        !(payload.source_cell instanceof HTMLElement)
    ) {
        store.commit("swimlane/removeHighlightOnLastHoveredDropZone");
        return false;
    }

    if (hasCardBeenDroppedInAnotherSwimlane(payload.target_cell, payload.source_cell)) {
        store.commit("swimlane/removeHighlightOnLastHoveredDropZone");

        return false;
    }

    store.commit("swimlane/removeHighlightOnLastHoveredDropZone");
    store.commit("swimlane/setLastHoveredDropZone", payload.target_cell);

    if (!isDropAcceptedInTarget(payload.dropped_card, payload.target_cell)) {
        store.commit("swimlane/setHighlightOnLastHoveredDropZone");

        return false;
    }

    return true;
}

function isDropAcceptedInTarget(dropped_card: HTMLElement, target_cell: HTMLElement): boolean {
    const tracker_id: string | undefined = dropped_card.dataset.trackerId;
    const accepted_trackers_ids: string | undefined = target_cell.dataset.acceptedTrackersIds;

    if (!tracker_id || !accepted_trackers_ids) {
        return false;
    }

    return accepted_trackers_ids.split(",").includes(tracker_id);
}
