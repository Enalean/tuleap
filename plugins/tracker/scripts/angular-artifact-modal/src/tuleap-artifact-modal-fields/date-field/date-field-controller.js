import { datePicker } from "tlp";

export default DateFieldController;

DateFieldController.$inject = ["$element"];

function DateFieldController($element) {
    const self = this;

    const DATE_PICKER_SIZE = 11;
    const DATETIME_PICKER_SIZE = 19;

    Object.assign(self, {
        $onInit: init,
        getFieldSize,
        isRequiredAndEmpty,
    });

    function init() {
        const date_picker = $element[0]
            .querySelector(".tlp-form-element")
            .getElementsByTagName("input")[0];

        if (self.field.is_time_displayed) {
            date_picker.setAttribute("data-enabletime", true);
        }

        const options = {
            onChange: function (selected_dates, currently_selected_date) {
                self.value_model.value = currently_selected_date;
            },
        };

        datePicker(date_picker, options);
    }

    function getFieldSize() {
        return self.field.is_time_displayed ? DATETIME_PICKER_SIZE : DATE_PICKER_SIZE;
    }

    function isRequiredAndEmpty() {
        return self.field.required && self.value_model.value === "";
    }
}
