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

import { createListPicker } from "@tuleap/list-picker";
import type { GetText } from "@tuleap/vue2-gettext-init";
import { disabledPlannableTrackers } from "../helper/disabled-plannable-tracker-helper";
import { getHTMLSelectElementFromId } from "../helper/HTML_element_extractor";
import { disabledIterationTrackersFromProgramIncrementAndPlannableTrackers } from "../helper/disabled-iteration-tracker-helper";
import { ITERATION_SELECT_ID } from "../helper/init-preview-labels-helper";

export const PROGRAM_INCREMENT_TRACKER_ID = "admin-configuration-program-increment-tracker";
export const PLANNABLE_TRACKERS_ID = "admin-configuration-plannable-trackers";
export const PERMISSION_PRIORITIZE_ID = "admin-configuration-permission-prioritize";

export function initListPickersMilestoneSection(doc: Document, gettext_provider: GetText): void {
    const program_increment_tracker_element = doc.getElementById(PROGRAM_INCREMENT_TRACKER_ID);

    if (
        !program_increment_tracker_element ||
        !(program_increment_tracker_element instanceof HTMLSelectElement)
    ) {
        return;
    }

    const plannable_trackers_element = getHTMLSelectElementFromId(doc, PLANNABLE_TRACKERS_ID);

    const permission_prioritize_element = getHTMLSelectElementFromId(doc, PERMISSION_PRIORITIZE_ID);

    createListPicker(program_increment_tracker_element, {
        locale: doc.body.dataset.userLocale,
        placeholder: gettext_provider.gettext("Choose a source tracker for Program Increments"),
        is_filterable: true,
    });

    createListPicker(plannable_trackers_element, {
        locale: doc.body.dataset.userLocale,
        placeholder: gettext_provider.gettext("Choose which trackers can be planned"),
        is_filterable: true,
    });

    createListPicker(permission_prioritize_element, {
        locale: doc.body.dataset.userLocale,
        placeholder: gettext_provider.gettext("Choose who can prioritize and plan items"),
        is_filterable: true,
    });

    disabledPlannableTrackers(doc, program_increment_tracker_element);

    program_increment_tracker_element.addEventListener("change", (event) => {
        if (!(event.target instanceof HTMLSelectElement)) {
            throw new Error("Target element is not HTMLSelectElement");
        }

        disabledPlannableTrackers(doc, event.target);
    });

    setIterationSection(
        doc,
        program_increment_tracker_element,
        plannable_trackers_element,
        gettext_provider,
    );
}

function setIterationSection(
    doc: Document,
    program_increment_tracker_element: HTMLSelectElement,
    plannable_trackers_element: HTMLSelectElement,
    gettext_provider: GetText,
): void {
    const iteration_trackers_element = getHTMLSelectElementFromId(doc, ITERATION_SELECT_ID);

    createListPicker(iteration_trackers_element, {
        locale: doc.body.dataset.userLocale,
        placeholder: gettext_provider.gettext("Choose a source tracker for Iterations"),
        is_filterable: true,
    });

    disabledIterationTrackersFromProgramIncrementAndPlannableTrackers(
        doc,
        program_increment_tracker_element.value,
        [...plannable_trackers_element.selectedOptions].map((option) => option.value),
    );

    program_increment_tracker_element.addEventListener("change", (event) => {
        if (!(event.target instanceof HTMLSelectElement)) {
            throw new Error("Target element is not HTMLSelectElement");
        }

        disabledIterationTrackersFromProgramIncrementAndPlannableTrackers(
            doc,
            event.target.value,
            [...plannable_trackers_element.selectedOptions].map((option) => option.value),
        );
    });

    plannable_trackers_element.addEventListener("change", (event) => {
        if (!(event.target instanceof HTMLSelectElement)) {
            throw new Error("Target element is not HTMLSelectElement");
        }

        disabledIterationTrackersFromProgramIncrementAndPlannableTrackers(
            doc,
            program_increment_tracker_element.value,
            [...event.target.selectedOptions].map((option) => option.value),
        );
    });
}
