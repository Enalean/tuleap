/*
 * Copyright (c) Enalean, 2017-present. All Rights Reserved.
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

import { validateOpenListFieldValue } from "./fields/open-list-field/open-list-field-validate-service.js";
import { formatComputedFieldValue } from "./adapters/UI/fields/computed-field/computed-field-value-formatter.ts";
import { formatPermissionFieldValue } from "./adapters/UI/fields/permission-field/permission-field-value-formatter";
import { validateFileField } from "./adapters/UI/fields/file-field/file-field-validator.ts";
import { FILE_FIELD, TEXT_FIELD } from "@tuleap/plugin-tracker-constants";

export function validateArtifactFieldsValues(
    field_values,
    creation_mode,
    followup_value_model,
    link_field_value_formatter,
) {
    const text_field_value_models = Object.values(field_values).filter(
        ({ type }) => type === TEXT_FIELD,
    );

    return Object.values(field_values)
        .filter(function (field) {
            return filterFieldPermissions(field, creation_mode);
        })
        .map(function (field) {
            switch (field.type) {
                case "computed":
                    return formatComputedFieldValue(field);
                case "perm":
                    return formatPermissionFieldValue(field);
                case "tbl":
                    return validateOpenListFieldValue(field);
                case "art_link":
                    return link_field_value_formatter.getFormattedValuesByFieldId(field.field_id);
                case FILE_FIELD:
                    return validateFileField(field, text_field_value_models, followup_value_model);
                default:
                    return validateOtherFields(field);
            }
        })
        .filter(Boolean);
}

function filterFieldPermissions(field, creation_mode) {
    if (field === undefined) {
        return false;
    }
    const necessary_permission = creation_mode ? "create" : "update";
    return (field.permissions || []).includes(necessary_permission);
}

function validateOtherFields(field) {
    if (!filterAtLeastOneAttribute(field)) {
        return;
    }

    if (field.value !== undefined) {
        field = validateValue(field);
    } else if (Array.isArray(field.bind_value_ids)) {
        field.bind_value_ids = field.bind_value_ids.filter((value_id) => {
            return value_id;
        });
    }

    return removeUnusedAttributes(field);
}

function filterAtLeastOneAttribute(field) {
    if (field === undefined) {
        return false;
    }

    const value_defined = field.value !== undefined;
    const bind_value_ids_present = Boolean(field.bind_value_ids);

    // This is a logical XOR: only one of those 2 attributes may be present at the same time on a given field
    return (value_defined && !bind_value_ids_present) || (!value_defined && bind_value_ids_present);
}

function validateValue(field) {
    switch (field.type) {
        case "date":
        case "int":
        case "float":
        case "string":
            if (field.value === null) {
                field.value = "";
            }
            break;
        default:
            break;
    }
    return field;
}

function removeUnusedAttributes(field) {
    const attributes_to_keep = {};

    if (field.bind_value_ids !== undefined) {
        attributes_to_keep.bind_value_ids = field.bind_value_ids;
    }
    if (field.field_id !== undefined) {
        attributes_to_keep.field_id = field.field_id;
    }
    if (field.value !== undefined) {
        attributes_to_keep.value = field.value;
    }
    return attributes_to_keep;
}
