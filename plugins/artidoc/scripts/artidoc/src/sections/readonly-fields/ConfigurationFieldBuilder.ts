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
import {
    CHECKBOX_FIELD,
    LIST_BIND_UGROUPS,
    MULTI_SELECTBOX_FIELD,
    OPEN_LIST_FIELD,
    RADIO_BUTTON_FIELD,
    SELECTBOX_FIELD,
    STRING_FIELD as TRACKER_STRING_FIELD,
} from "@tuleap/plugin-tracker-constants";
import type { ConfigurationField } from "@/sections/readonly-fields/AvailableReadonlyFields";
import { DISPLAY_TYPE_COLUMN } from "@/sections/readonly-fields/AvailableReadonlyFields";
import { STRING_FIELD, USER_GROUP_LIST_FIELD } from "@/sections/readonly-fields/ReadonlyFields";
import { Option } from "@tuleap/option";

const buildConfiguredFieldIfSupported = (field: StructureFields): Option<ConfigurationField> => {
    const field_base = {
        field_id: field.field_id,
        label: field.label,
        display_type: DISPLAY_TYPE_COLUMN,
    };

    if (field.type === TRACKER_STRING_FIELD) {
        return Option.fromValue<ConfigurationField>({ ...field_base, type: STRING_FIELD });
    }

    if (
        (field.type === SELECTBOX_FIELD ||
            field.type === MULTI_SELECTBOX_FIELD ||
            field.type === OPEN_LIST_FIELD ||
            field.type === RADIO_BUTTON_FIELD ||
            field.type === CHECKBOX_FIELD) &&
        field.bindings.type === LIST_BIND_UGROUPS
    ) {
        return Option.fromValue<ConfigurationField>({ ...field_base, type: USER_GROUP_LIST_FIELD });
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
