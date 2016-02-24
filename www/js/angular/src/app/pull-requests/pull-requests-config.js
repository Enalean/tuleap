angular
    .module('tuleap.pull-request')
    .config(PullRequestsConfig);

PullRequestsConfig.$inject = [
    '$stateProvider'
];

function PullRequestsConfig(
    $stateProvider
) {
    $stateProvider.state('pull-requests', {
        url     : '/pull-requests',
        views   : {
            '': {
                template: '<div pull-requests id="pull-requests"></div>'
            }
        }
    });
}
