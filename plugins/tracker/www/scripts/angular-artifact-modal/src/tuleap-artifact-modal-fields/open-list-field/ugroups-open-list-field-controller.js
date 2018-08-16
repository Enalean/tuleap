import { select2 } from "tlp";
import { isUndefined } from "angular";

export default UgroupsOpenListFieldController;

UgroupsOpenListFieldController.$inject = ["$element"];

function UgroupsOpenListFieldController($element) {
    var self = this;
    self.init = init;
    self.isRequiredAndEmpty = isRequiredAndEmpty;

    self.init();

    function init() {
        var open_list_element = $element[0].querySelector(
            ".tuleap-artifact-modal-open-list-ugroups"
        );
        if (!open_list_element) {
            return;
        }

        select2(open_list_element, {
            placeholder: self.field.hint,
            allowClear: true
        });
    }

    function isRequiredAndEmpty() {
        return self.field.required && isUndefined(self.value_model.value.bind_value_objects);
    }
}
