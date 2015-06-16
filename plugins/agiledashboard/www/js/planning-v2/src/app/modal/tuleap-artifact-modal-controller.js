angular
    .module('tuleap.artifact-modal')
    .controller('TuleapArtifactModalCtrl', TuleapArtifactModalCtrl);

TuleapArtifactModalCtrl.$inject = ['$modalInstance', 'modal_model', 'displayItemCallback', 'TuleapArtifactModalRestService', 'TuleapArtifactModalValidateService', 'TuleapArtifactModalLoading'];

function TuleapArtifactModalCtrl($modalInstance, modal_model, displayItemCallback, TuleapArtifactModalRestService, TuleapArtifactModalValidateService, TuleapArtifactModalLoading) {
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
        getError        : function() { return TuleapArtifactModalRestService.error; },
        isLoading       : function() { return TuleapArtifactModalRestService.is_loading; },
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
        TuleapArtifactModalLoading.loading.is_loading = false;
    });

    function submit() {
        TuleapArtifactModalLoading.loading.is_loading = true;

        var validated_values = TuleapArtifactModalValidateService.validateArtifactFieldsValues(self.values, modal_model.creation_mode);

        var promise;
        if (modal_model.creation_mode) {
            promise = TuleapArtifactModalRestService.createArtifact(modal_model.tracker_id, validated_values);
        } else {
            promise = TuleapArtifactModalRestService.editArtifact(modal_model.artifact_id, validated_values);
        }

        promise.then(function(new_artifact) {
            $modalInstance.close();
            return displayItemCallback(new_artifact.id);
        })["finally"](function() {
            TuleapArtifactModalLoading.loading.is_loading = false;
        });
    }

    function toggleFieldset(fieldset) {
        fieldset.collapsed = ! fieldset.collapsed;
    }
}
