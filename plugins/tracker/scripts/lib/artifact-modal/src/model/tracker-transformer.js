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

import AwkwardCreationFields from "./awkward-creation-fields-constant.js";
import { STRUCTURAL_FIELDS } from "@tuleap/plugin-tracker-constants";
import { getNone } from "../gettext-catalog";

export function transform(tracker, creation_mode) {
    const transformed_tracker = { ...tracker };

    if (creation_mode) {
        transformed_tracker.fields = excludeFieldsForCreationMode(tracker.fields);
    }
    if (hasFieldDependenciesRules(transformed_tracker)) {
        transformed_tracker.workflow.rules.lists =
            transformFieldDependenciesRules(transformed_tracker);
    }
    transformed_tracker.fields = transformFields(transformed_tracker.fields);

    return transformed_tracker;
}

function excludeFieldsForCreationMode(all_fields) {
    const filtered_fields = all_fields.filter((field) => {
        return !AwkwardCreationFields.includes(field.type);
    });
    return excludeFieldsWithoutCreationPermissions(filtered_fields);
}

function excludeFieldsWithoutCreationPermissions(fields) {
    return fields.filter((field) => {
        const is_structural_field = fieldIsAStructuralField(field);
        if (is_structural_field) {
            return true;
        }
        return field.permissions.includes("create");
    });
}

function fieldIsAStructuralField(field) {
    return STRUCTURAL_FIELDS.includes(field.type);
}

function transformFields(all_fields) {
    return all_fields.map(transformField);
}

function transformField(field) {
    switch (field.type) {
        case "sb":
            field.values = filterHiddenValues(field.values);
            field.values = displayI18NLabelIfAvailable(field.values);
            field.values = addNoneValueInSelectboxField(field);
            break;
        case "msb":
            field.values = filterHiddenValues(field.values);
            field.values = displayI18NLabelIfAvailable(field.values);
            field.values = addNoneValueInMultiSelectboxField(field);
            field.filtered_values = { ...field.values };
            break;
        case "cb":
        case "rb":
            field.values = filterHiddenValues(field.values);
            field.values = displayI18NLabelIfAvailable(field.values);
            break;
        case "tbl":
            if (field.bindings.type === "users" || !field.values) {
                field.values = [];
            }
            field.loading = false;
            break;
        case "computed":
            field.value = null;
            break;
        default:
            break;
    }

    return field;
}

function filterHiddenValues(field_values) {
    return field_values.filter((field_value) => {
        return !field_value.is_hidden;
    });
}

function displayI18NLabelIfAvailable(field_values) {
    return field_values.map((value) => {
        if (value.user_reference !== undefined) {
            return { ...value, label: value.user_reference.real_name };
        } else if (value.ugroup_reference !== undefined) {
            return {
                ...value,
                id: value.ugroup_reference.id,
                label: value.ugroup_reference.label,
            };
        }
        return value;
    });
}

function addNoneValueInSelectboxField(selectbox_field) {
    if (!selectbox_field.has_transitions) {
        selectbox_field.values.unshift({
            id: 100,
            label: getNone(),
        });
    }

    return selectbox_field.values;
}

function addNoneValueInMultiSelectboxField(multi_selectbox_field) {
    if (!multi_selectbox_field.required && !multi_selectbox_field.has_transitions) {
        multi_selectbox_field.values.unshift({
            id: 100,
            label: getNone(),
        });
    }

    return multi_selectbox_field.values;
}

function hasFieldDependenciesRules(tracker) {
    return (
        tracker.workflow !== undefined &&
        tracker.workflow.rules !== undefined &&
        tracker.workflow.rules.lists !== undefined
    );
}

function transformFieldDependenciesRules(tracker) {
    const new_rules = arrayDeepCopy(tracker.workflow.rules.lists);
    const fields = arrayDeepCopy(tracker.fields);
    const fields_bound_to_ugroups = getListFieldsBoundToUgroups(fields);

    return new_rules.map((rule) => {
        const source_field = fields_bound_to_ugroups[rule.source_field_id];
        const replaced_source_field_rule = replaceSourceFieldValueIdWithUgroupReferenceId(
            rule,
            source_field,
        );

        const target_field = fields_bound_to_ugroups[replaced_source_field_rule.target_field_id];
        return replaceTargetFieldValueIdWithUgroupReferenceId(
            replaced_source_field_rule,
            target_field,
        );
    });
}
function arrayDeepCopy(source_copy) {
    let target_copy, value, key;

    if (!(source_copy instanceof Object)) {
        return source_copy;
    }

    target_copy = Array.isArray(source_copy) ? [] : {};

    for (key in source_copy) {
        value = source_copy[key];

        // Recursively (deep) copy for nested objects, including arrays
        target_copy[key] = arrayDeepCopy(value);
    }

    return target_copy;
}

function getListFieldsBoundToUgroups(fields) {
    // keyBy function extracted from https://github.com/you-dont-need/You-Dont-Need-Lodash-Underscore/tree/v6.10.0#_keyBy
    const keyBy = (array, key) =>
        (array || []).reduce((r, x) => ({ ...r, [key ? x[key] : x]: x }), {});

    return keyBy(
        fields
            .filter((field) => {
                if (!Object.prototype.hasOwnProperty.call(field, "bindings")) {
                    return false;
                }
                const bindings = field.bindings;

                return (
                    Object.prototype.hasOwnProperty.call(bindings, "type") &&
                    bindings.type === "ugroups"
                );
            })
            .map(function (field) {
                field.values = keyBy(field.values, "id");

                return field;
            }),
        "field_id",
    );
}

function replaceSourceFieldValueIdWithUgroupReferenceId(field_dependency_rule, source_field) {
    if (source_field && field_dependency_rule.source_value_id !== 100) {
        field_dependency_rule.source_value_id =
            source_field.values[field_dependency_rule.source_value_id].ugroup_reference.id;
    }

    return field_dependency_rule;
}

function replaceTargetFieldValueIdWithUgroupReferenceId(field_dependency_rule, target_field) {
    if (target_field && field_dependency_rule.target_value_id !== 100) {
        field_dependency_rule.target_value_id =
            target_field.values[field_dependency_rule.target_value_id].ugroup_reference.id;
    }

    return field_dependency_rule;
}

export function addFieldValuesToTracker(artifact_values, tracker) {
    const transformed_fields = tracker.fields.map((field) => {
        var artifact_value = artifact_values[field.field_id];

        if (!artifact_value) {
            return field;
        }

        // We attach the value to the tracker to avoid submitting it
        switch (true) {
            case isAwkwardFieldForCreation(field.type):
                return addReadOnlyFieldValueToTracker(field, artifact_value);
            case field.type === "perm":
                return addPermissionFieldValueToTracker(field, artifact_value);
            case field.type === "file":
                return addFileFieldValueToTracker(field, artifact_value);
            case field.type === "computed":
                return addComputedFieldValueToTracker(field, artifact_value);
            default:
                break;
        }

        return field;
    });

    tracker.fields = transformed_fields;

    return tracker;
}

function isAwkwardFieldForCreation(type) {
    return AwkwardCreationFields.includes(type);
}

function addReadOnlyFieldValueToTracker(field, artifact_value) {
    if (artifact_value.value) {
        field.value = artifact_value.value;
    }

    return field;
}

function addPermissionFieldValueToTracker(field, artifact_value) {
    if (artifact_value.granted_groups) {
        field.values.is_used_by_default =
            artifact_value.granted_groups && artifact_value.granted_groups.length > 0;
    }

    return field;
}

function addFileFieldValueToTracker(field, artifact_value) {
    if (!artifact_value.file_descriptions) {
        return field;
    }

    field.file_descriptions = artifact_value.file_descriptions;

    field.file_descriptions = field.file_descriptions.map((file) => {
        const display_as_image = /^image/.test(file.type);
        return { ...file, display_as_image };
    });

    return field;
}

function addComputedFieldValueToTracker(field, artifact_value) {
    if (artifact_value.value === undefined) {
        field.value = null;
    } else {
        field.value = artifact_value.value;
    }

    return field;
}
