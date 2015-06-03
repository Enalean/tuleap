angular
    .module('modal')
    .controller('ModalInstanceCtrl', ModalInstanceCtrl);

ModalInstanceCtrl.$inject = ['$modalInstance', 'modal_model', 'displayItemCallback', 'ModalTuleapFactory', 'ModalValidateFactory', 'ModalLoading'];

function ModalInstanceCtrl($modalInstance, modal_model, displayItemCallback, ModalTuleapFactory, ModalValidateFactory, ModalLoading) {
    var self = this;

    _.extend(self, {
        title         : modal_model.title,
        structure     : modal_model.structure,
        values        : modal_model.values,
        cancel        : $modalInstance.dismiss,
        createArtifact: createArtifact,
        toggleFieldset: toggleFieldset,
        getError      : function() { return ModalTuleapFactory.error; },
        isLoading     : function() { return ModalTuleapFactory.is_loading; },

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
        ],
        parent_artifacts: []
    });

    $modalInstance.opened.then(function() {
        ModalLoading.loading.is_loading = false;
    });

    function createArtifact() {
        var validated_values = ModalValidateFactory.validateArtifactFieldsValues(self.values);

        ModalTuleapFactory.createArtifact(modal_model.tracker_id, validated_values).then(function(new_artifact) {
            $modalInstance.close();
            displayItemCallback(new_artifact.id);
        });
    }

    function toggleFieldset(fieldset) {
        fieldset.collapsed = ! fieldset.collapsed;
    }
}
