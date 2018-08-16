import { copy } from "angular";
import _ from "lodash";

export default WorkflowService;

WorkflowService.$inject = [];

function WorkflowService() {
    var self = this;
    self.enforceWorkflowTransitions = enforceWorkflowTransitions;

    function enforceWorkflowTransitions(source_value_id, field, workflow) {
        var field_values = getPossibleValues(source_value_id, field.values, workflow.transitions);

        field.values = field_values;
        field.filtered_values = copy(field_values);
        field.has_transitions = true;

        return field;
    }

    function getPossibleValues(source_value_id, field_values, transitions) {
        var possible_value_ids = getPossibleValueIds(source_value_id, transitions);

        var filtered_values = _.filter(field_values, function(value) {
            return _.contains(possible_value_ids, value.id);
        });

        return filtered_values;
    }

    function getPossibleValueIds(source_value_id, transitions) {
        return _(transitions)
            .filter(function(transition) {
                return transition.from_id === source_value_id;
            })
            .pluck("to_id")
            .push(source_value_id)
            .compact()
            .value();
    }
}
