angular
    .module('modal')
    .service('ModalService', ModalService)
    .value('ModalLoading', {
        loading: {
            is_loading: false
        }
    });

ModalService.$inject = ['$q', '$modal', 'ModalTuleapFactory', 'ModalModelFactory', 'ModalLoading'];

function ModalService($q, $modal, ModalTuleapFactory, ModalModelFactory, ModalLoading) {
    var self = this;

    _.extend(self, {
        getArtifactValues      : getArtifactValues,
        getParentArtifactsTitle: getParentArtifactsTitle,
        initModalModel         : initModalModel,
        show                   : show
    });

    /**
     * Opens a new modal pop-in which will display a form with all the fields defined in the
     * given tracker's structure.
     * displayItemCallback will be called after the last HTTP response is received
     *
     * @param  {int} tracker_id               The tracker to which the item we want to add/edit belongs
     * @param  {function} displayItemCallback The function to call after receiving the last HTTP response. It will be called with the new artifact's id or the edited artifact's id.
     */
    function show(tracker_id, displayItemCallback, artifact_id, color) {
        ModalLoading.loading.is_loading = true;

        return $modal.open({
            backdrop   : 'static',
            templateUrl: 'modal/modal.tpl.html',
            controller : 'ModalInstanceCtrl as modal',
            resolve    : {
                modal_model: function() {
                    return self.initModalModel(tracker_id, artifact_id, color);
                },
                displayItemCallback: function() {
                    var cb = (displayItemCallback) ? displayItemCallback : angular.noop;
                    return cb;
                }
            }
        });
    }

    function initModalModel(tracker_id, artifact_id, color) {
        var modal_model = {};

        modal_model.tracker_id  = tracker_id;
        modal_model.artifact_id = artifact_id;
        modal_model.color       = color;

        var promise = ModalTuleapFactory.getTrackerStructure(tracker_id).then(function(structure) {
            modal_model.structure      = structure;
            modal_model.ordered_fields = ModalModelFactory.reorderFieldsInGoodOrder(structure);

            var second_promise = self.getParentArtifactsTitle(structure.parent, modal_model);
            var third_promise  = self.getArtifactValues(artifact_id, structure, modal_model);

            return $q.all([second_promise, third_promise]);
        }).then(function() {
            if (artifact_id) {
                modal_model.creation_mode = false;
                if (modal_model.structure.semantics && modal_model.structure.semantics.title) {
                    var title_field_id = modal_model.structure.semantics.title.field_id;
                    modal_model.title  = modal_model.values[title_field_id].value;
                }
            } else {
                modal_model.creation_mode = true;
                modal_model.title         = modal_model.structure.label;
            }
            return modal_model;
        });
        return promise;
    }

    function getParentArtifactsTitle(parent, modal_model) {
        modal_model.parent = parent;

        var promise;
        if (parent != null) {
            promise = ModalTuleapFactory.getArtifactsTitles(parent.id)
            .then(function(artifacts_data) {
                modal_model.parent_artifacts = artifacts_data;
            });
        }
        return $q.when(promise);
    }

    function getArtifactValues(artifact_id, structure, modal_model) {
        var promise;
        if (artifact_id) {
            promise = ModalTuleapFactory.getArtifact(artifact_id);
        }
        var result = $q.when(promise).then(function(artifact_data) {
            var artifact_values = (artifact_data && artifact_data.values) ? artifact_data.values : [];
            modal_model.values = ModalModelFactory.createFromStructure(artifact_values, structure);
        });

        return result;
    }
}
