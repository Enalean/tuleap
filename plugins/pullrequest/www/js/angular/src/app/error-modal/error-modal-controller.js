angular
    .module('tuleap.pull-request')
    .controller('ErrorModalController', ErrorModalController);

ErrorModalController.$inject = [
    'lodash',
    '$modalInstance',
    'message'
];

function ErrorModalController(
    lodash,
    $modalInstance,
    message
) {
    var self = this;

    lodash.extend(self, {
        reloading: false,
        details  : false,
        message  : message,
        ok       : ok
    });

    function ok() {
        $modalInstance.close();
    }
}
