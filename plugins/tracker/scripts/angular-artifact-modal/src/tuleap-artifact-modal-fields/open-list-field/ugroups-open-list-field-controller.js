import { select2 } from "tlp";
import { isUndefined } from "angular";

export default UgroupsOpenListFieldController;

UgroupsOpenListFieldController.$inject = ["$element"];

function UgroupsOpenListFieldController($element) {
    const self = this;
    self.$onInit = init;
    self.isRequiredAndEmpty = isRequiredAndEmpty;

    function init() {
        const open_list_element = $element[0].querySelector(
            ".tuleap-artifact-modal-open-list-ugroups"
        );
        if (!open_list_element) {
            return;
        }

        select2(open_list_element, {
            placeholder: self.field.hint,
            allowClear: true,
        });
    }

    function isRequiredAndEmpty() {
        return self.field.required && isUndefined(self.value_model.value.bind_value_objects);
    }
}
