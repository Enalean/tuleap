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

import { ITERATION_SELECT_ID } from "./init-preview-labels-helper";

export function disabledIterationTrackersFromProgramIncrementAndPlannableTrackers(
    doc: Document,
    program_increment_value: string,
    plannable_trackers_values: string[],
): void {
    const iteration_tracker_element = doc.getElementById(ITERATION_SELECT_ID);

    if (!(iteration_tracker_element instanceof HTMLSelectElement)) {
        throw new Error("Iteration tracker element does not exist");
    }

    for (const iteration_tracker of iteration_tracker_element.options) {
        iteration_tracker.disabled = isDisabled(
            iteration_tracker.value,
            program_increment_value,
            plannable_trackers_values,
        );
    }
}

function isDisabled(
    iteration_tracker_id: string,
    program_increment_value: string,
    plannable_trackers_values: string[],
): boolean {
    if (iteration_tracker_id === program_increment_value) {
        return true;
    }

    return plannable_trackers_values.includes(iteration_tracker_id);
}
