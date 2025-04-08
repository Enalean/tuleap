/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { getTracker } from "@/helpers/rest-querier";
import type { TrackerResponseNoInstance } from "@tuleap/plugin-tracker-rest-api-types";

export type TrackerForFields = Pick<TrackerResponseNoInstance, "fields" | "semantics">;

export type ConfigurationField = {
    readonly type: "string";
    readonly field_id: number;
    readonly label: string;
    display_type: "column" | "block";
};

export const getAvailableFields = (
    tracker_id: number,
): ResultAsync<ConfigurationField[], Fault> => {
    return getTracker(tracker_id).map((tracker): ConfigurationField[] => {
        const string_fields = getStringFields(tracker.fields);
        return ignoreSemanticsTitle(tracker, string_fields);
    });
};

export function getStringFields(all_fields: TrackerForFields["fields"]): ConfigurationField[] {
    const string_fields: ConfigurationField[] = [];
    all_fields.forEach((field) => {
        if (field.type === "string") {
            string_fields.push({ ...field, display_type: "column" });
        }
    });
    return string_fields;
}

export function ignoreSemanticsTitle(
    tracker: TrackerForFields,
    string_fields: ConfigurationField[],
): ConfigurationField[] {
    const title_field_id = tracker.semantics.title?.field_id;
    return string_fields.filter((field) => field.field_id !== title_field_id);
}
