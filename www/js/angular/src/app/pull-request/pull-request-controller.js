angular
    .module('tuleap.pull-request')
    .controller('PullRequestController', PullRequestController);

PullRequestController.$inject = [
    '$state',
    'lodash'
];

function PullRequestController(
    $state,
    lodash
) {
    var self = this;

    lodash.extend(self, {
        $state: $state
    });
}
