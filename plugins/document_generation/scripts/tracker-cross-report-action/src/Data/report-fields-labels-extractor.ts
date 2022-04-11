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

import type { ArtifactResponse } from "@tuleap/plugin-docgen-docx";
import { isFieldTakenIntoAccount } from "./field-type-checker";

export function extractFieldsLabels(
    artifact_representations_map: Map<number, ArtifactResponse>,
    artifact_ids: Array<number>
): Array<string> {
    if (artifact_representations_map.size === 0 || artifact_ids.length === 0) {
        throw new Error("This must not happen. Check must be done before.");
    }

    const first_artifact_id_in_first_level = artifact_ids[0];
    const first_artifact_in_first_level = artifact_representations_map.get(
        first_artifact_id_in_first_level
    );

    if (first_artifact_in_first_level === undefined) {
        throw new Error("This must not happen. Collection must be consistent.");
    }

    const fields_labels: Array<string> = [];
    for (const field_value of first_artifact_in_first_level.values) {
        if (!isFieldTakenIntoAccount(field_value)) {
            continue;
        }

        fields_labels.push(field_value.label);
    }

    return fields_labels;
}
