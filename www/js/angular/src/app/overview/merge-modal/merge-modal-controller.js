angular
    .module('tuleap.pull-request')
    .controller('MergeModalController', MergeModalController);

MergeModalController.$inject = [
    'lodash',
    '$modalInstance'
];

function MergeModalController(
    lodash,
    $modalInstance
) {
    var self = this;

    lodash.extend(self, {
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
