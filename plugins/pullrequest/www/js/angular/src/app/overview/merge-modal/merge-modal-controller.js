angular
    .module('tuleap.pull-request')
    .controller('MergeModalController', MergeModalController);

MergeModalController.$inject = [
    'lodash',
    '$modalInstance'
];

function MergeModalController(
    _,
    $modalInstance
) {
    var self = this;

    _.extend(self, {
        proceed: proceed,
        cancel : cancel
    });

    function proceed() {
        $modalInstance.close();
    }

    function cancel() {
        $modalInstance.dismiss();
    }
}
