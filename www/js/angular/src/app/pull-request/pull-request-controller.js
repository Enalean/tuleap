angular
    .module('tuleap.pull-request')
    .controller('PullRequestController', PullRequestController);

PullRequestController.$inject = [
    '$state',
    'lodash'
];

function PullRequestController(
    $state,
    _
) {
    var self = this;

    _.extend(self, {
        $state: $state
    });
}
