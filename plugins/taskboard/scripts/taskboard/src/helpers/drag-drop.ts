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
import { hasCardBeenDroppedInTheSameCell } from "./html-to-item";

export function isContainer(element?: Element): boolean {
    if (!element) {
        return false;
    }
    return (
        element.classList.contains("taskboard-cell") &&
        !element.classList.contains("taskboard-cell-swimlane-header") &&
        !element.classList.contains("taskboard-swimlane-collapsed-cell-placeholder") &&
        !element.classList.contains("taskboard-card-parent")
    );
}

export function canMove(element?: Element, handle?: Element): boolean {
    if (!element || !handle) {
        return false;
    }

    return (
        !element.classList.contains("taskboard-card-collapsed") &&
        !handle.classList.contains("taskboard-item-no-drag") &&
        element.classList.contains("taskboard-card")
    );
}

export function accepts(element?: Element, target?: Element, source?: Element): boolean {
    if (!(target instanceof HTMLElement) || !(source instanceof HTMLElement)) {
        return false;
    }

    return hasCardBeenDroppedInTheSameCell(target, source);
}
