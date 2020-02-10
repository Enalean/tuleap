export default ComputedFieldController;

ComputedFieldController.$inject = [];

function ComputedFieldController() {
    var self = this;
    self.switchToAutocomputed = switchToAutocomputed;
    self.switchToManual = switchToManual;

    function switchToAutocomputed() {
        self.value_model.is_autocomputed = true;
        self.value_model.manual_value = null;
    }

    function switchToManual() {
        self.value_model.is_autocomputed = false;
    }
}
