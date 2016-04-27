angular
    .module('tuleap.pull-request')
    .controller('PullRequestController', PullRequestController);

PullRequestController.$inject = [
    '$state',
    'lodash',
    'SharedPropertiesService'
];

function PullRequestController(
    $state,
    lodash,
    SharedPropertiesService
) {
    var self = this;

    // TODO use presenters in template instead of asking $state
    lodash.extend(self, {
        $state: $state
    });

    if ($state.current.name === 'pull-request') {
        var prId = SharedPropertiesService.getPullRequest().id;
        $state.go('overview', { id: prId });
    }
}
