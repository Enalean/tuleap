import { select2 } from "tlp";
import { isDefined } from "angular";
import { unique, union, remove, findIndex, find } from "lodash";

export default StaticOpenListFieldController;

StaticOpenListFieldController.$inject = ["$element"];

function StaticOpenListFieldController($element) {
    var self = this;

    self.init = init;
    self.handleStaticValueSelection = handleStaticValueSelection;
    self.handleStaticValueUnselection = handleStaticValueUnselection;
    self.isStaticValueSelected = isStaticValueSelected;
    self.newOpenListStaticValue = newOpenListStaticValue;
    self.isRequiredAndEmpty = isRequiredAndEmpty;
    self.fieldValues = fieldValues;

    self.init();

    function init() {
        self.merged_values = [];

        var open_list_element = $element[0].querySelector(
            ".tuleap-artifact-modal-open-list-static"
        );
        if (!open_list_element) {
            return;
        }

        select2(open_list_element, {
            placeholder: self.field.hint,
            allowClear: true,
            tags: true,
            createTag: self.newOpenListStaticValue
        });

        $element.on("select2:selecting", self.handleStaticValueSelection);

        $element.on("select2:unselecting", self.handleStaticValueUnselection);
    }

    function fieldValues() {
        if (self.merged_values.length === 0) {
            var union_values = union(self.field.values, self.value_model.value.bind_value_objects);
            self.merged_values = unique(union_values, "label");
        }
        return self.merged_values;
    }

    function isRequiredAndEmpty() {
        return self.field.required && self.value_model.value.bind_value_objects.length === 0;
    }

    function isStaticValueSelected(field_value) {
        var found = find(self.value_model.value.bind_value_objects, function(value_object) {
            return value_object.id === field_value.id.toString();
        });

        return isDefined(found);
    }

    function handleStaticValueSelection(event) {
        var new_selection = event.params.args.data;
        var new_value_model_value = {
            label: new_selection.text
        };

        if (new_selection.isTag !== true) {
            new_value_model_value["id"] = new_selection.id;
        }

        self.value_model.value.bind_value_objects.push(new_value_model_value);
    }

    function handleStaticValueUnselection(event) {
        var removed_selection = event.params.args.data;

        remove(self.value_model.value.bind_value_objects, function(value_object) {
            if (removed_selection.isTag === true) {
                return value_object.label === removed_selection.text;
            }
            return value_object.id === removed_selection.id;
        });
    }

    function newOpenListStaticValue(new_open_value) {
        var term = new_open_value.term.trim();

        if (term === "") {
            return null;
        }

        var tag_already_exists = findIndex(self.field.values, function(value) {
            return value.label.toLowerCase() === term.toLowerCase();
        });

        if (tag_already_exists !== -1) {
            return null;
        }

        return {
            id: term,
            text: term,
            isTag: true
        };
    }
}
