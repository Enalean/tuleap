angular
    .module('modal')
    .controller('ModalInstanceCtrl', ModalInstanceCtrl);

ModalInstanceCtrl.$inject = ['$modalInstance', 'modal_model', 'displayItemCallback', 'ModalTuleapFactory', 'ModalValidateFactory', 'ModalLoading'];

function ModalInstanceCtrl($modalInstance, modal_model, displayItemCallback, ModalTuleapFactory, ModalValidateFactory, ModalLoading) {
    var self = this;

    _.extend(self, {
        artifact_id     : modal_model.artifact_id,
        color           : modal_model.color,
        creation_mode   : modal_model.creation_mode,
        ordered_fields  : modal_model.ordered_fields,
        parent          : modal_model.parent,
        parent_artifacts: modal_model.parent_artifacts,
        structure       : modal_model.structure,
        title           : modal_model.title,
        values          : modal_model.values,
        cancel          : $modalInstance.dismiss,
        getError        : function() { return ModalTuleapFactory.error; },
        isLoading       : function() { return ModalTuleapFactory.is_loading; },
        submit          : submit,
        toggleFieldset  : toggleFieldset,

        ckeditor_options: {
            toolbar: [
                ['Bold', 'Italic', 'Underline'],
                ['NumberedList', 'BulletedList', '-', 'Blockquote', 'Format'],
                ['Link', 'Unlink', 'Anchor', 'Image'],
                ['Source']
            ]
        },
        text_formats: [
            { id: "text", label:"Text" },
            { id: "html", label:"HTML" }
        ]
    });

    $modalInstance.opened.then(function() {
        ModalLoading.loading.is_loading = false;
    });

    function submit() {
        ModalLoading.loading.is_loading = true;

        var validated_values = ModalValidateFactory.validateArtifactFieldsValues(self.values);

        var promise;
        if (modal_model.creation_mode) {
            promise = ModalTuleapFactory.createArtifact(modal_model.tracker_id, validated_values);
        } else {
            promise = ModalTuleapFactory.editArtifact(modal_model.artifact_id, validated_values);
        }

        promise.then(function(new_artifact) {
            $modalInstance.close();
            return displayItemCallback(new_artifact.id);
        })["finally"](function() {
            ModalLoading.loading.is_loading = false;
        });
    }

    function toggleFieldset(fieldset) {
        fieldset.collapsed = ! fieldset.collapsed;
    }
}
