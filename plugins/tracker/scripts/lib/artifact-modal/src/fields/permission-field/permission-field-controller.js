export default PermissionFieldController;

PermissionFieldController.$inject = [];

function PermissionFieldController() {
    var self = this;

    self.clearSelectBox = clearSelectBox;
    self.isSelectBoxDisabled = isSelectBoxDisabled;
    self.isSelectBoxRequired = isSelectBoxRequired;

    function clearSelectBox() {
        if (self.value_model.value.is_used_by_default === null) {
            self.value_model.value.granted_groups = [];
        }
    }

    function isSelectBoxDisabled() {
        if (self.field.required) {
            return self.isDisabled();
        }

        return !self.value_model.value.is_used_by_default || self.isDisabled();
    }

    function isSelectBoxRequired() {
        if (self.value_model.value.is_used_by_default === true) {
            return true;
        }

        return self.field.required;
    }
}
