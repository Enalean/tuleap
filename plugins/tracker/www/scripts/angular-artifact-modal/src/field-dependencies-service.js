import _ from "lodash";

export default function TuleapArtifactModalFieldDependenciesService() {
    var self = this;
    _.extend(self, {
        getTargetFieldPossibleValues: getTargetFieldPossibleValues,
        setUpFieldDependenciesActions: setUpFieldDependenciesActions
    });

    function setUpFieldDependenciesActions(tracker, callback) {
        var field_dependencies_rules = getFieldDependenciesRules(tracker);

        _(field_dependencies_rules)
            .unique(false, "target_field_id")
            .forEach(function(rule) {
                var target_field = _.find(tracker.fields, { field_id: rule.target_field_id });

                if (_.isFunction(callback)) {
                    callback(rule.source_field_id, target_field, field_dependencies_rules);
                }
            });
    }

    function getFieldDependenciesRules(tracker) {
        if (
            _.has(tracker, "workflow") &&
            _.has(tracker.workflow, "rules") &&
            _.has(tracker.workflow.rules, "lists")
        ) {
            return tracker.workflow.rules.lists;
        }

        return undefined;
    }

    function getTargetFieldPossibleValues(
        source_value_ids,
        target_field,
        field_dependencies_rules
    ) {
        var possible_value_ids = getPossibleTargetValueIds(
            source_value_ids,
            target_field.field_id,
            field_dependencies_rules
        );

        var filtered_values = _.filter(target_field.values, function(value) {
            return _(possible_value_ids).contains(value.id);
        });

        return filtered_values;
    }

    function getPossibleTargetValueIds(
        source_value_ids,
        target_field_id,
        field_dependencies_rules
    ) {
        return _(field_dependencies_rules)
            .filter(function(rule) {
                return (
                    _(source_value_ids).contains(rule.source_value_id) &&
                    rule.target_field_id === target_field_id
                );
            })
            .pluck("target_value_id")
            .value();
    }
}
