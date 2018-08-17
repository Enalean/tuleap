import _ from "lodash";
import { copy, isUndefined } from "angular";

export default TuleapArtifactModalTrackerTransformerService;

TuleapArtifactModalTrackerTransformerService.$inject = [
    "$filter",
    "TuleapArtifactModalAwkwardCreationFields",
    "TuleapArtifactModalStructuralFields"
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
        var filtered_fields = _.reject(all_fields, function(field) {
            return _(TuleapArtifactModalAwkwardCreationFields).contains(field.type);
        });
        var filtered_fields_without_creation_permissions = excludeFieldsWithoutCreationPermissions(
            filtered_fields
        );

        return filtered_fields_without_creation_permissions;
    }

    function excludeFieldsWithoutCreationPermissions(fields) {
        var fields_with_create_permissions = _.filter(fields, function(field) {
            var structural_field = fieldIsAStructuralField(field);
            if (!structural_field) {
                return _.contains(field.permissions, "create");
            }
            return true;
        });

        return fields_with_create_permissions;
    }

    function fieldIsAStructuralField(field) {
        return _(TuleapArtifactModalStructuralFields).contains(field.type);
    }

    function transformFields(all_fields) {
        var transformed_fields = _.map(all_fields, transformField);

        return transformed_fields;
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
        _.map(field_values, function(value) {
            if (value.user_reference !== undefined) {
                value.label = value.user_reference.real_name;
            } else if (value.ugroup_reference !== undefined) {
                value.id = value.ugroup_reference.id;
                value.label = value.ugroup_reference.label;
            }
        });

        return field_values;
    }

    function addNoneValue(field) {
        if (!field.required && !field.has_transitions) {
            var translateFilter = $filter("translate");
            field.values.unshift({
                id: 100,
                label: translateFilter("None")
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

        _(new_rules).map(function(rule) {
            var source_field = fields_bound_to_ugroups[rule.source_field_id];
            rule = replaceSourceFieldValueIdWithUgroupReferenceId(rule, source_field);

            var target_field = fields_bound_to_ugroups[rule.target_field_id];
            rule = replaceTargetFieldValueIdWithUgroupReferenceId(rule, target_field);

            return rule;
        });

        return new_rules;
    }

    function getListFieldsBoundToUgroups(fields) {
        return _(fields)
            .filter({ bindings: { type: "ugroups" } })
            .map(function(field) {
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
        var transformed_fields = _.map(tracker.fields, function(field) {
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

        _.map(field.file_descriptions, function(file) {
            file.display_as_image = /^image/.test(file.type);

            return file;
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
