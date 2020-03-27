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

import _ from "lodash";
import { validateOpenListFieldValue } from "./tuleap-artifact-modal-fields/open-list-field/open-list-field-validate-service.js";
import { formatComputedFieldValue } from "./tuleap-artifact-modal-fields/computed-field/computed-field-value-formatter.js";
import { formatPermissionFieldValue } from "./tuleap-artifact-modal-fields/permission-field/permission-field-value-formatter.js";
import { formatLinkFieldValue } from "./tuleap-artifact-modal-fields/link-field/link-field-value-formatter.js";
import { validateFileField } from "./tuleap-artifact-modal-fields/file-field/file-field-validator.js";
import { FILE_FIELD, TEXT_FIELD } from "../../constants/fields-constants.js";

export default ValidateService;

ValidateService.$inject = [];

function ValidateService() {
    return {
        validateArtifactFieldsValues,
    };

    function validateArtifactFieldsValues(field_values, creation_mode, followup_value_model) {
        const text_field_value_models = Object.values(field_values).filter(
            ({ type }) => type === TEXT_FIELD
        );
        var validated_values = _(field_values)
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
                        return formatLinkFieldValue(field);
                    case FILE_FIELD:
                        return validateFileField(
                            field,
                            text_field_value_models,
                            followup_value_model
                        );
                    default:
                        return validateOtherFields(field);
                }
            })
            .compact()
            .value();
        return validated_values;
    }

    function filterFieldPermissions(field, creation_mode) {
        if (field === undefined) {
            return false;
        }
        var necessary_permission = creation_mode ? "create" : "update";
        return _(field.permissions).contains(necessary_permission);
    }

    function validateOtherFields(field) {
        if (!filterAtLeastOneAttribute(field)) {
            return;
        }

        if (field.value !== undefined) {
            field = validateValue(field);
        } else if (Array.isArray(field.bind_value_ids)) {
            field.bind_value_ids = _.compact(field.bind_value_ids);
        }

        return removeUnusedAttributes(field);
    }

    function filterAtLeastOneAttribute(field) {
        if (field === undefined) {
            return false;
        }

        var value_defined = field.value !== undefined;
        var bind_value_ids_present = Boolean(field.bind_value_ids);

        // This is a logical XOR: only one of those 2 attributes may be present at the same time on a given field
        return (
            (value_defined && !bind_value_ids_present) || (!value_defined && bind_value_ids_present)
        );
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
        var attributes_to_keep = _.pick(field, function (property, key) {
            switch (key) {
                case "bind_value_ids":
                case "field_id":
                case "value":
                    return property !== undefined;
                default:
                    return false;
            }
        });
        return attributes_to_keep;
    }
}
