angular
    .module('tuleap.pull-request')
    .config(PullRequestConfig);

PullRequestConfig.$inject = [
    '$stateProvider'
];

function PullRequestConfig(
    $stateProvider
) {
    $stateProvider.state('pull-request', {
        url   : '/{id:[0-9]+}',
        parent: 'pull-requests',
        views : {
            'pull-request@pull-requests': {
                template: '<div pull-request id="pull-request"></div>'
            },
            'files@pull-request': {
                template: '<div files id="files"></div>'
            },
            'comments@pull-request': {
                template: '<div comments id="comments"></div>'
            }
        }
    });
}
