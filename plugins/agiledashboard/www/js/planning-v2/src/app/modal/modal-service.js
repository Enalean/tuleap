angular
    .module('modal')
    .service('ModalService', ModalService);

ModalService.$inject = ['$modal'];

function ModalService($modal) {
    var self = this;
    self.show = show;

    /**
     * Opens a new modal pop-in which will display a form with all the fields defined in the
     * given tracker's structure.
     * displayItemCallback will be called after the last HTTP response is received
     * @param  {int} tracker_id               The tracker to which the item we want to add/edit belongs
     * @param  {function} displayItemCallback The function to call after receiving the last HTTP response. It will be called with the new artifact's id.
     */
    function show(tracker_id, displayItemCallback) {
        $modal.open({
            backdrop: 'static',
            templateUrl: 'modal/modal.tpl.html',
            controller: 'ModalInstanceCtrl as modal',
            resolve: {
                tracker_id: function() {
                    return tracker_id;
                },
                displayItemCallback: function() {
                    var cb = (displayItemCallback) ? displayItemCallback : angular.noop;
                    return cb;
                }
            }
        });
    }
}
