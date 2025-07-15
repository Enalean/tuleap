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
import type {
    StructureFields,
    TrackerResponseNoInstance,
} from "@tuleap/plugin-tracker-rest-api-types";
import type {
    LINKS_FIELD,
    STATIC_LIST_FIELD,
    STRING_FIELD,
    USER_GROUP_LIST_FIELD,
    USER_LIST_FIELD,
} from "@/sections/readonly-fields/ReadonlyFields";
import { ConfigurationFieldBuilder } from "@/sections/readonly-fields/ConfigurationFieldBuilder";

export type TrackerForFields = Pick<TrackerResponseNoInstance, "fields" | "semantics">;

export const DISPLAY_TYPE_COLUMN = "column";
export const DISPLAY_TYPE_BLOCK = "block";
export type ConfigurationFieldDisplayType = typeof DISPLAY_TYPE_COLUMN | typeof DISPLAY_TYPE_BLOCK;

export type ConfigurationFieldType =
    | typeof STRING_FIELD
    | typeof LINKS_FIELD
    | typeof USER_GROUP_LIST_FIELD
    | typeof STATIC_LIST_FIELD
    | typeof USER_LIST_FIELD;

export type ConfigurationField = {
    readonly type: ConfigurationFieldType;
    readonly field_id: number;
    readonly label: string;
    readonly can_display_type_be_changed: boolean;
    display_type: ConfigurationFieldDisplayType;
};

export const getAvailableFields = (
    tracker_id: number,
    selected_fields: ConfigurationField[],
): ResultAsync<ConfigurationField[], Fault> => {
    return getTracker(tracker_id).map((tracker): ConfigurationField[] => {
        const supported_fields = getSupportedFields(tracker.fields);

        return filterAlreadySelectedFields(
            filterSemanticTitleBoundField(tracker, supported_fields),
            selected_fields,
        );
    });
};

export function getSupportedFields(
    all_fields: ReadonlyArray<StructureFields>,
): ConfigurationField[] {
    const supported_fields: ConfigurationField[] = [];
    all_fields.forEach((field) => {
        ConfigurationFieldBuilder.fromTrackerField(field).apply((configuration_field) =>
            supported_fields.push(configuration_field),
        );
    });
    return supported_fields;
}

export function filterSemanticTitleBoundField(
    tracker: TrackerForFields,
    string_fields: ConfigurationField[],
): ConfigurationField[] {
    const title_field_id = tracker.semantics.title?.field_id;
    return string_fields.filter((field) => field.field_id !== title_field_id);
}

export function filterAlreadySelectedFields(
    available_fields: ConfigurationField[],
    selected_fields: ConfigurationField[],
): ConfigurationField[] {
    return available_fields.filter(
        (field) =>
            selected_fields.findIndex(
                (selected_field) => selected_field.field_id === field.field_id,
            ) === -1,
    );
}
