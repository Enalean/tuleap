/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import { copy } from "angular";
import _ from "lodash";
import AwkwardCreationFields from "./awkward-creation-fields-constant.js";
import { formatExistingValue as formatForLinkField } from "../tuleap-artifact-modal-fields/link-field/link-field-initializer.js";
import { formatExistingValue as formatForDateField } from "../tuleap-artifact-modal-fields/date-field/date-field-initializer.js";
import {
    formatDefaultValue as defaultForOpenListField,
    formatExistingValue as formatForOpenListField,
} from "../tuleap-artifact-modal-fields/open-list-field/open-list-field-initializer.js";

export default TuleapArtifactFieldValuesService;

TuleapArtifactFieldValuesService.$inject = ["$sce"];

function TuleapArtifactFieldValuesService($sce) {
    const self = this;
    self.getSelectedValues = getSelectedValues;

    /**
     * For every field in the tracker, creates a field object with the value from the given artifact
     * or the field's default value if there is no artifact and there is a default value.
     * @param  {Array} artifact_values            A map of artifact values from the edited artifact field_id: { field_id, value|bind_value_ids } OR an empty object
     * @param  {TrackerRepresentation} tracker    The tracker as returned from the REST route
     * @return {Object}                           A map of objects indexed by field_id => { field_id, value|bind_value_ids }
     */
    function getSelectedValues(artifact_values, tracker) {
        var values = {};
        var artifact_value;

        tracker.fields.forEach((field) => {
            artifact_value = artifact_values[field.field_id];

            if (AwkwardCreationFields.includes(field.type)) {
                values[field.field_id] = {
                    field_id: field.field_id,
                    type: field.type,
                };
            } else if (artifact_value) {
                values[field.field_id] = formatExistingValue(field, artifact_value);
            } else {
                values[field.field_id] = getDefaultValue(field);
            }
        });

        return values;
    }

    function formatExistingValue(field, artifact_value) {
        var value_obj = copy(artifact_value);
        value_obj.type = field.type;
        value_obj.permissions = field.permissions;

        switch (field.type) {
            case "date":
                value_obj = formatForDateField(field, artifact_value);
                break;
            case "cb":
                value_obj.bind_value_ids = mapCheckboxValues(field, artifact_value.bind_value_ids);
                break;
            case "sb":
            case "msb":
            case "rb":
                value_obj.bind_value_ids = !_.isEmpty(artifact_value.bind_value_ids)
                    ? artifact_value.bind_value_ids
                    : [100];
                break;
            case "text":
                value_obj.value = {
                    content: artifact_value.value,
                    format: artifact_value.format,
                };
                delete value_obj.format;
                break;
            case "perm":
                value_obj.value = {
                    is_used_by_default: field.values.is_used_by_default,
                    granted_groups: artifact_value.granted_groups_ids,
                };
                delete value_obj.granted_groups_ids;
                break;
            case "tbl":
                value_obj = formatForOpenListField(field, artifact_value);
                break;
            case "file":
                value_obj = addPropertiesToFileValueModel(value_obj);
                value_obj.value = _.pluck(artifact_value.file_descriptions, "id");
                break;
            case "computed":
                delete value_obj.value;
                break;
            case "art_link":
                value_obj = formatForLinkField(field, artifact_value);
                break;
            default:
                break;
        }

        return value_obj;
    }

    function getDefaultValue(field) {
        var value_obj = {
            field_id: field.field_id,
            type: field.type,
            permissions: field.permissions,
        };
        var default_value;
        switch (field.type) {
            case "sb":
                default_value = _.pluck(field.default_value, "id");
                if (field.has_transitions) {
                    // the default value may not be a valid transition value
                    if (
                        field.default_value &&
                        defaultValueExistsInValues(field.values, default_value)
                    ) {
                        value_obj.bind_value_ids = [].concat(default_value);
                    } else if (field.values[0]) {
                        value_obj.bind_value_ids = [].concat(field.values[0].id);
                    }
                } else {
                    value_obj.bind_value_ids = !_.isEmpty(default_value)
                        ? [].concat(default_value)
                        : [100];
                }
                break;
            case "msb":
                default_value = _.pluck(field.default_value, "id");
                value_obj.bind_value_ids = !_.isEmpty(default_value)
                    ? [].concat(default_value)
                    : [100];
                break;
            case "cb":
                default_value = _.pluck(field.default_value, "id");
                value_obj.bind_value_ids = mapCheckboxValues(field, default_value);
                break;
            case "rb":
                default_value = _.pluck(field.default_value, "id");
                value_obj.bind_value_ids = !_.isEmpty(default_value) ? default_value : [100];
                break;
            case "int":
                value_obj.value = field.default_value ? parseInt(field.default_value, 10) : null;
                break;
            case "float":
                value_obj.value = field.default_value ? parseFloat(field.default_value, 10) : null;
                break;
            case "text":
                value_obj.value = {
                    content: null,
                    format: "text",
                };
                if (field.default_value) {
                    value_obj.value.format = field.default_value.format;
                    value_obj.value.content = field.default_value.content;
                }
                break;
            case "string":
            case "date":
                value_obj.value = field.default_value ? field.default_value : null;
                break;
            case "art_link":
                value_obj.unformatted_links = "";
                value_obj.links = [{ id: "" }];
                break;
            case "staticrichtext":
                value_obj.default_value = $sce.trustAsHtml(field.default_value);
                break;
            case "perm":
                value_obj.value = {
                    is_used_by_default: field.values.is_used_by_default,
                    granted_groups: [],
                };
                break;
            case "tbl":
                value_obj = defaultForOpenListField(field);
                break;
            case "file":
                value_obj = addPropertiesToFileValueModel(value_obj);
                value_obj.value = [];
                break;
            case "computed":
                default_value = field.default_value;
                if (default_value === null) {
                    value_obj.is_autocomputed = true;
                    value_obj.manual_value = null;
                } else {
                    value_obj.is_autocomputed = false;
                    value_obj.manual_value = parseFloat(default_value.value, 10);
                }
                break;
            default:
                // Do nothing
                break;
        }
        return value_obj;
    }

    function addPropertiesToFileValueModel(value_obj) {
        value_obj.temporary_files = [
            {
                file: {},
                description: "",
            },
        ];
        value_obj.images_added_by_text_fields = [];
        return value_obj;
    }

    function defaultValueExistsInValues(values, default_value_id) {
        const found = values.find((value) => default_value_id === value.id);
        return found !== undefined;
    }

    function mapCheckboxValues(field, expected_values) {
        return field.values.map((possible_value) => {
            return expected_values.includes(possible_value.id) ? possible_value.id : null;
        });
    }
}
