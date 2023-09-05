/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import type { OrganizedReportsData } from "../type";
import { TextCell } from "@tuleap/plugin-docgen-xlsx";
import { extractFieldsLabels } from "./report-fields-labels-extractor";

export function formatReportsFieldsLabels(
    organized_data: OrganizedReportsData,
): ReadonlyArray<TextCell> {
    if (organized_data.first_level.artifact_representations.size === 0) {
        throw new Error("This must not happen. Check must be done before.");
    }

    const report_fields_labels: Array<TextCell> = [];
    for (const field_label of extractFieldsLabels(
        organized_data.first_level.artifact_representations,
    )) {
        report_fields_labels.push(new TextCell(field_label));
    }

    if (
        organized_data.second_level &&
        organized_data.second_level.artifact_representations.size > 0
    ) {
        for (const field_label of extractFieldsLabels(
            organized_data.second_level.artifact_representations,
        )) {
            report_fields_labels.push(new TextCell(field_label));
        }
    }

    if (
        organized_data.third_level &&
        organized_data.third_level.artifact_representations.size > 0
    ) {
        for (const field_label of extractFieldsLabels(
            organized_data.third_level.artifact_representations,
        )) {
            report_fields_labels.push(new TextCell(field_label));
        }
    }

    return report_fields_labels;
}
