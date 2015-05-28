angular
    .module('modal')
    .controller('ModalInstanceCtrl', ModalInstanceCtrl);

ModalInstanceCtrl.$inject = ['$modalInstance', 'tracker_id', 'displayItemCallback', 'ModalTuleapFactory', 'ModalModelFactory', 'ModalValidateFactory'];

function ModalInstanceCtrl($modalInstance, tracker_id, displayItemCallback, ModalTuleapFactory, ModalModelFactory, ModalValidateFactory) {
    var self = this;
    _.extend(self, {
        activate      : activate,
        cancel        : $modalInstance.dismiss,
        createArtifact: createArtifact,
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
        parent_artifacts: [],
        values          : []
    });
    activate();

    function createArtifact() {
        var validated_values = ModalValidateFactory.validateArtifactFieldsValues(self.values);
        ModalTuleapFactory.createArtifact(tracker_id, validated_values).then(function(new_artifact) {
            $modalInstance.close();
            displayItemCallback(new_artifact.id);
        });
    }

    function activate() {
        return $modalInstance.opened.then(function() {
            return ModalTuleapFactory.getTrackerStructure(tracker_id);
        }).then(function(data) {
            self.structure = data;
            self.values = ModalModelFactory.createFromStructure(data);

            var parent_tracker_id;
            if (self.structure.parent != null) {
                parent_tracker_id = self.structure.parent.id;
                ModalTuleapFactory.getArtifactsTitles(parent_tracker_id).then(function(data) {
                    self.parent_artifacts = data;
                });
            }
        });
    }
}
