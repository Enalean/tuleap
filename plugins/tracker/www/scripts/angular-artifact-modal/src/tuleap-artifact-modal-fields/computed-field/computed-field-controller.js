angular
    .module('tuleap-artifact-modal-computed-field')
    .controller('TuleapArtifactModalComputedFieldController', TuleapArtifactModalComputedFieldController);

function TuleapArtifactModalComputedFieldController() {
    var self = this;
    _.extend(self, {
        switchToAutocomputed: switchToAutocomputed,
        switchToManual      : switchToManual
    });

    function switchToAutocomputed() {
        self.value_model.is_autocomputed = true;
        self.value_model.manual_value    = null;
    }

    function switchToManual() {
        self.value_model.is_autocomputed = false;
    }
}
