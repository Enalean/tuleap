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

import _ from "lodash";
import { copy, isUndefined } from "angular";

export default TuleapArtifactModalTrackerTransformerService;

TuleapArtifactModalTrackerTransformerService.$inject = [
    "$filter",
    "TuleapArtifactModalAwkwardCreationFields",
    "TuleapArtifactModalStructuralFields",
];

function TuleapArtifactModalTrackerTransformerService(
    $filter,
    TuleapArtifactModalAwkwardCreationFields,
    TuleapArtifactModalStructuralFields
) {
    var self = this;
    self.addFieldValuesToTracker = addFieldValuesToTracker;
    self.transform = transform;

    function transform(tracker, creation_mode) {
        var transformed_tracker = copy(tracker);

        if (creation_mode) {
            transformed_tracker.fields = excludeFieldsForCreationMode(tracker.fields);
        }

        if (hasFieldDependenciesRules(transformed_tracker)) {
            transformed_tracker.workflow.rules.lists = transformFieldDependenciesRules(
                transformed_tracker
            );
        }

        transformed_tracker.fields = transformFields(transformed_tracker.fields);

        return transformed_tracker;
    }

    function excludeFieldsForCreationMode(all_fields) {
        var filtered_fields = _.reject(all_fields, function (field) {
            return _(TuleapArtifactModalAwkwardCreationFields).contains(field.type);
        });
        var filtered_fields_without_creation_permissions = excludeFieldsWithoutCreationPermissions(
            filtered_fields
        );

        return filtered_fields_without_creation_permissions;
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
        return TuleapArtifactModalStructuralFields.includes(field.type);
    }

    function transformFields(all_fields) {
        return all_fields.map(transformField);
    }

    function transformField(field) {
        switch (field.type) {
            case "sb":
                field.values = filterHiddenValues(field.values);
                field.values = displayI18NLabelIfAvailable(field.values);
                field.values = addNoneValue(field);
                field.filtered_values = copy(field.values);
                break;
            case "msb":
                field.values = filterHiddenValues(field.values);
                field.values = displayI18NLabelIfAvailable(field.values);
                field.values = addNoneValue(field);
                field.filtered_values = copy(field.values);
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
        return _.reject(field_values, { is_hidden: true });
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

    function addNoneValue(field) {
        if (!field.required && !field.has_transitions) {
            var translateFilter = $filter("translate");
            field.values.unshift({
                id: 100,
                label: translateFilter("None"),
            });
        }

        return field.values;
    }

    function hasFieldDependenciesRules(tracker) {
        return (
            _.has(tracker, "workflow") &&
            _.has(tracker.workflow, "rules") &&
            _.has(tracker.workflow.rules, "lists")
        );
    }

    function transformFieldDependenciesRules(tracker) {
        var new_rules = copy(tracker.workflow.rules.lists);
        var fields = copy(tracker.fields);
        var fields_bound_to_ugroups = getListFieldsBoundToUgroups(fields);

        return new_rules.map((rule) => {
            const source_field = fields_bound_to_ugroups[rule.source_field_id];
            const replaced_source_field_rule = replaceSourceFieldValueIdWithUgroupReferenceId(
                rule,
                source_field
            );

            const target_field =
                fields_bound_to_ugroups[replaced_source_field_rule.target_field_id];
            return replaceTargetFieldValueIdWithUgroupReferenceId(
                replaced_source_field_rule,
                target_field
            );
        });
    }

    function getListFieldsBoundToUgroups(fields) {
        return _(fields)
            .filter({ bindings: { type: "ugroups" } })
            .map(function (field) {
                field.values = _.indexBy(field.values, "id");

                return field;
            })
            .indexBy("field_id")
            .value();
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

    function addFieldValuesToTracker(artifact_values, tracker) {
        const transformed_fields = tracker.fields.map((field) => {
            var artifact_value = artifact_values[field.field_id];

            if (!artifact_value) {
                return field;
            }

            // We attach the value to the tracker to avoid submitting it
            switch (true) {
                case isAwkwardFieldForCration(field.type):
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

    function isAwkwardFieldForCration(type) {
        return _(TuleapArtifactModalAwkwardCreationFields).contains(type);
    }

    function addReadOnlyFieldValueToTracker(field, artifact_value) {
        if (artifact_value.value) {
            field.value = artifact_value.value;
        }

        return field;
    }

    function addPermissionFieldValueToTracker(field, artifact_value) {
        if (artifact_value.granted_groups) {
            field.values.is_used_by_default = !_.isEmpty(artifact_value.granted_groups);
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
        if (isUndefined(artifact_value.value)) {
            field.value = null;
        } else {
            field.value = artifact_value.value;
        }

        return field;
    }
}
