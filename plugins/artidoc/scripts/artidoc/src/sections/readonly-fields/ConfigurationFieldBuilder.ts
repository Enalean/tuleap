/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

import type { StructureFields } from "@tuleap/plugin-tracker-rest-api-types";
import type { ListBindType } from "@tuleap/plugin-tracker-constants";
import {
    ARTIFACT_ID_FIELD,
    ARTIFACT_ID_IN_TRACKER_FIELD,
    ARTIFACT_LINK_FIELD,
    CHECKBOX_FIELD,
    COMPUTED_FIELD,
    DATE_FIELD as TRACKER_DATE_FIELD,
    FLOAT_FIELD,
    INT_FIELD,
    LAST_UPDATE_DATE_FIELD,
    LAST_UPDATED_BY_FIELD,
    LIST_BIND_STATIC,
    LIST_BIND_UGROUPS,
    LIST_BIND_USERS,
    MULTI_SELECTBOX_FIELD,
    OPEN_LIST_FIELD,
    PERMISSION_FIELD as TRACKER_PERMISSIONS_FIELD,
    PRIORITY_FIELD,
    RADIO_BUTTON_FIELD,
    SELECTBOX_FIELD,
    STRING_FIELD as TRACKER_STRING_FIELD,
    SUBMISSION_DATE_FIELD,
    SUBMITTED_BY_FIELD,
    TEXT_FIELD as TRACKER_TEXT_FIELD,
} from "@tuleap/plugin-tracker-constants";
import type {
    ConfigurationField,
    ConfigurationFieldType,
} from "@/sections/readonly-fields/AvailableReadonlyFields";
import {
    DISPLAY_TYPE_BLOCK,
    DISPLAY_TYPE_COLUMN,
} from "@/sections/readonly-fields/AvailableReadonlyFields";
import {
    DATE_FIELD,
    LINKS_FIELD,
    NUMERIC_FIELD,
    PERMISSIONS_FIELD,
    STATIC_LIST_FIELD,
    TEXT_FIELD,
    USER_FIELD,
    USER_GROUP_LIST_FIELD,
    USER_LIST_FIELD,
} from "@/sections/readonly-fields/ReadonlyFields";
import { Option } from "@tuleap/option";

const buildConfiguredListFieldType = (list_bind_type: ListBindType): ConfigurationFieldType => {
    if (list_bind_type === LIST_BIND_STATIC) {
        return STATIC_LIST_FIELD;
    }
    if (list_bind_type === LIST_BIND_UGROUPS) {
        return USER_GROUP_LIST_FIELD;
    }
    if (list_bind_type === LIST_BIND_USERS) {
        return USER_LIST_FIELD;
    }
    throw new Error(`Unknown list bind type ${list_bind_type}`);
};

const isNumericField = (field: StructureFields): boolean => {
    const numeric_types: string[] = [
        ARTIFACT_ID_FIELD,
        ARTIFACT_ID_IN_TRACKER_FIELD,
        FLOAT_FIELD,
        INT_FIELD,
        PRIORITY_FIELD,
        COMPUTED_FIELD,
    ];
    return numeric_types.includes(field.type);
};

const buildConfiguredFieldIfSupported = (field: StructureFields): Option<ConfigurationField> => {
    const field_base = {
        field_id: field.field_id,
        label: field.label,
        can_display_type_be_changed: true,
    };

    if (field.type === TRACKER_STRING_FIELD) {
        return Option.fromValue<ConfigurationField>({
            ...field_base,
            display_type: DISPLAY_TYPE_COLUMN,
            type: TEXT_FIELD,
        });
    }

    if (field.type === TRACKER_TEXT_FIELD) {
        return Option.fromValue<ConfigurationField>({
            ...field_base,
            display_type: DISPLAY_TYPE_BLOCK,
            type: TEXT_FIELD,
            can_display_type_be_changed: false,
        });
    }

    if (isNumericField(field)) {
        return Option.fromValue<ConfigurationField>({
            ...field_base,
            display_type: DISPLAY_TYPE_COLUMN,
            type: NUMERIC_FIELD,
        });
    }

    if (field.type === ARTIFACT_LINK_FIELD) {
        return Option.fromValue<ConfigurationField>({
            ...field_base,
            display_type: DISPLAY_TYPE_BLOCK,
            type: LINKS_FIELD,
            can_display_type_be_changed: false,
        });
    }

    if (field.type === LAST_UPDATED_BY_FIELD || field.type === SUBMITTED_BY_FIELD) {
        return Option.fromValue<ConfigurationField>({
            ...field_base,
            display_type: DISPLAY_TYPE_COLUMN,
            type: USER_FIELD,
        });
    }

    if (
        field.type === SELECTBOX_FIELD ||
        field.type === MULTI_SELECTBOX_FIELD ||
        field.type === OPEN_LIST_FIELD ||
        field.type === RADIO_BUTTON_FIELD ||
        field.type === CHECKBOX_FIELD
    ) {
        return Option.fromValue<ConfigurationField>({
            ...field_base,
            display_type: DISPLAY_TYPE_COLUMN,
            type: buildConfiguredListFieldType(field.bindings.type),
        });
    }

    if (
        field.type === TRACKER_DATE_FIELD ||
        field.type === SUBMISSION_DATE_FIELD ||
        field.type === LAST_UPDATE_DATE_FIELD
    ) {
        return Option.fromValue<ConfigurationField>({
            ...field_base,
            display_type: DISPLAY_TYPE_COLUMN,
            type: DATE_FIELD,
        });
    }

    if (field.type === TRACKER_PERMISSIONS_FIELD) {
        return Option.fromValue<ConfigurationField>({
            ...field_base,
            display_type: DISPLAY_TYPE_COLUMN,
            type: PERMISSIONS_FIELD,
        });
    }

    return Option.nothing();
};

export const ConfigurationFieldBuilder = {
    fromTrackerField: (field: StructureFields): Option<ConfigurationField> =>
        buildConfiguredFieldIfSupported(field),
    fromSupportedTrackerField: (field: StructureFields): ConfigurationField => {
        const configured_field = buildConfiguredFieldIfSupported(field).unwrapOr(null);
        if (!configured_field) {
            throw Error(`Field with type ${field.type} is not supported`);
        }
        return configured_field;
    },
};
