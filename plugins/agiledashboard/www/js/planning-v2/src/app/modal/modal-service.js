angular
    .module('modal')
    .service('ModalService', ModalService)
    .value('ModalLoading', {
        loading: {
            is_loading: false
        }
    });

ModalService.$inject = ['$modal', 'ModalTuleapFactory', 'ModalModelFactory', 'ModalLoading'];

function ModalService($modal, ModalTuleapFactory, ModalModelFactory, ModalLoading) {
    var self = this;

    _.extend(self, {
        show          : show,
        initModalModel: initModalModel
    });

    /**
     * Opens a new modal pop-in which will display a form with all the fields defined in the
     * given tracker's structure.
     * displayItemCallback will be called after the last HTTP response is received
     *
     * @param  {int} tracker_id               The tracker to which the item we want to add/edit belongs
     * @param  {function} displayItemCallback The function to call after receiving the last HTTP response. It will be called with the new artifact's id.
     */
    function show(tracker_id, displayItemCallback) {
        ModalLoading.loading.is_loading = true;

        return $modal.open({
            backdrop   : 'static',
            templateUrl: 'modal/modal.tpl.html',
            controller : 'ModalInstanceCtrl as modal',
            resolve    : {
                modal_model: function() {
                    return self.initModalModel(tracker_id);
                },
                displayItemCallback: function() {
                    var cb = (displayItemCallback) ? displayItemCallback : angular.noop;
                    return cb;
                }
            }
        });
    }

    function initModalModel(tracker_id) {
        var modal_model = {};

        modal_model.tracker_id = tracker_id;

        var promise = ModalTuleapFactory.getTrackerStructure(tracker_id).then(function(response) {
            modal_model.title     = response.label;
            modal_model.structure = ModalTuleapFactory.reorderFieldsInGoodOrder(response);
            modal_model.values    = ModalModelFactory.createFromStructure(response);

            var parent_tracker_id;

            if (response.parent != null) {
                parent_tracker_id = response.parent.id;

                ModalTuleapFactory.getArtifactsTitles(parent_tracker_id).then(function(response) {
                    self.parent_artifacts = response;
                });
            }

            return modal_model;
        });

        return promise;
    }
}
