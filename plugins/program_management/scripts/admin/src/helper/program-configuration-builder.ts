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
import { getHTMLSelectElementFromId } from "./HTML_select_element_extractor";

export function buildProgramConfiguration(doc: Document, program_id: number): ProgramConfiguration {
    const program_increment_tracker_element = getHTMLSelectElementFromId(
        doc,
        "admin-configuration-program-increment-tracker"
    );

    return {
        program_id,
        program_increment_tracker_id: Number.parseInt(
            program_increment_tracker_element.selectedOptions[0].value,
            10
        ),
        permissions: { can_prioritize_features: extractOptionsFromPermissions(doc) },
        plannable_tracker_ids: extractOptionsFromPlannableTrackers(doc),
        program_increment_label: extractInputLabel(
            doc,
            "admin-configuration-program-increment-label-section"
        ),
        program_increment_sub_label: extractInputLabel(
            doc,
            "admin-configuration-program-increment-sub-label-section"
        ),
    };
}

function extractOptionsFromPlannableTrackers(doc: Document): number[] {
    const plannable_trackers_element = getHTMLSelectElementFromId(
        doc,
        "admin-configuration-plannable-trackers"
    );

    const value = [];
    for (const selectedOption of plannable_trackers_element.selectedOptions) {
        value.push(Number.parseInt(selectedOption.value, 10));
    }
    return value;
}

function extractOptionsFromPermissions(doc: Document): string[] {
    const permission_prioritize_element = getHTMLSelectElementFromId(
        doc,
        "admin-configuration-permission-prioritize"
    );

    const value = [];
    for (const selectedOption of permission_prioritize_element.selectedOptions) {
        value.push(selectedOption.value);
    }

    return value;
}

function extractInputLabel(doc: Document, element_id: string): string {
    const program_increment_label = doc.getElementById(element_id);

    if (!program_increment_label || !(program_increment_label instanceof HTMLInputElement)) {
        throw new Error("No " + element_id + " input");
    }

    return program_increment_label.value;
}
