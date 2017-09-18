angular
    .module('tuleap.pull-request')
    .directive('pullRequest', PullRequestDirective);

function PullRequestDirective() {
    return {
        restrict        : 'A',
        scope           : {},
        templateUrl     : 'pull-request/pull-request.tpl.html',
        controller      : 'PullRequestController as pull_request',
        bindToController: true
    };
}
