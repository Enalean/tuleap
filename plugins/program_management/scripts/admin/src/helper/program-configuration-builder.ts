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
import type { ProgramConfiguration } from "../type";
import { getHTMLInputElementFromId, getHTMLSelectElementFromId } from "./HTML_element_extractor";
import {
    PERMISSION_PRIORITIZE_ID,
    PLANNABLE_TRACKERS_ID,
    PROGRAM_INCREMENT_TRACKER_ID,
} from "../milestones/init-list-pickers-milestone-section";
import {
    ITERATION_SELECT_ID,
    ITERATIONS_LABEL_ID,
    ITERATIONS_SUB_LABEL_ID,
    PROGRAM_INCREMENT_LABEL_ID,
    PROGRAM_INCREMENT_SUB_LABEL_ID,
} from "./init-preview-labels-helper";

export function buildProgramConfiguration(doc: Document, program_id: number): ProgramConfiguration {
    const program_increment_tracker_element = getHTMLSelectElementFromId(
        doc,
        PROGRAM_INCREMENT_TRACKER_ID,
    );

    return {
        program_id,
        program_increment_tracker_id: Number.parseInt(
            program_increment_tracker_element.selectedOptions[0].value,
            10,
        ),
        permissions: { can_prioritize_features: extractOptionsFromPermissions(doc) },
        plannable_tracker_ids: extractOptionsFromPlannableTrackers(doc),
        program_increment_label: getHTMLInputElementFromId(doc, PROGRAM_INCREMENT_LABEL_ID).value,
        program_increment_sub_label: getHTMLInputElementFromId(doc, PROGRAM_INCREMENT_SUB_LABEL_ID)
            .value,
        iteration: extractIterationConfigurationObject(doc),
    };
}

function extractOptionsFromPlannableTrackers(doc: Document): number[] {
    const plannable_trackers_element = getHTMLSelectElementFromId(doc, PLANNABLE_TRACKERS_ID);

    const value = [];
    for (const selectedOption of plannable_trackers_element.selectedOptions) {
        value.push(Number.parseInt(selectedOption.value, 10));
    }
    return value;
}

function extractOptionsFromPermissions(doc: Document): string[] {
    const permission_prioritize_element = getHTMLSelectElementFromId(doc, PERMISSION_PRIORITIZE_ID);

    const value = [];
    for (const selectedOption of permission_prioritize_element.selectedOptions) {
        value.push(selectedOption.value);
    }

    return value;
}

function extractIterationConfigurationObject(
    doc: Document,
): null | { iteration_tracker_id: number; iteration_label?: string; iteration_sub_label?: string } {
    const iteration_tracker_element = getHTMLSelectElementFromId(doc, ITERATION_SELECT_ID);
    if (iteration_tracker_element.value === "") {
        return null;
    }

    return {
        iteration_tracker_id: Number.parseInt(iteration_tracker_element.value, 10),
        iteration_label: getHTMLInputElementFromId(doc, ITERATIONS_LABEL_ID).value,
        iteration_sub_label: getHTMLInputElementFromId(doc, ITERATIONS_SUB_LABEL_ID).value,
    };
}
