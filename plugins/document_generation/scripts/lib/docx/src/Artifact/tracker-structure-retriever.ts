/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import { get } from "@tuleap/tlp-fetch";
import type { FieldsStructure, TrackerDefinition, TrackerStructure } from "../type";

export async function retrieveTrackerStructure(tracker_id: number): Promise<TrackerStructure> {
    const tracker_structure: TrackerDefinition = await getTrackerDefinition(tracker_id);

    const fields_map: Map<number, FieldsStructure> = new Map();

    for (const field of tracker_structure.fields) {
        switch (field.type) {
            case "date":
            case "lud":
            case "subon":
            case "fieldset":
            case "sb":
            case "rb":
            case "msb":
            case "cb":
            case "tbl":
            case "perm":
            case "ttmstepexec":
                fields_map.set(field.field_id, field);
                break;
            default:
        }
    }

    return { fields: fields_map, disposition: tracker_structure.structure };
}

export async function getTrackerDefinition(tracker_id: number): Promise<TrackerDefinition> {
    const tracker_structure_response = await get(
        `/api/v1/trackers/${encodeURIComponent(tracker_id)}`,
    );
    return tracker_structure_response.json();
}
