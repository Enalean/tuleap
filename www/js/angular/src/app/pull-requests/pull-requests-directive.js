angular
    .module('tuleap.pull-request')
    .directive('pullRequests', PullRequestsDirective);

function PullRequestsDirective() {
    return {
        restrict        : 'A',
        scope           : {},
        templateUrl     : 'pull-requests/pull-requests.tpl.html',
        controller      : 'PullRequestsController as pullrequests_controller',
        bindToController: true
    };
}
