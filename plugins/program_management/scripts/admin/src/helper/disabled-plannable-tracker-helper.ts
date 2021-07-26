/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
import { PLANNABLE_TRACKERS_ID } from "../milestones/init-list-pickers-milestone-section";

export function disabledPlannableTrackers(doc: Document, selector: HTMLSelectElement): void {
    const plannable_trackers_element = doc.getElementById(PLANNABLE_TRACKERS_ID);

    if (!plannable_trackers_element || !(plannable_trackers_element instanceof HTMLSelectElement)) {
        throw new Error("Plannable trackers element does not exist");
    }

    for (const plannable_tracker of plannable_trackers_element.options) {
        plannable_tracker.disabled = plannable_tracker.value === selector.value;
    }
}
