angular
    .module('tuleap.pull-request')
    .directive('pullRequestHeader', PullRequestHeaderDirective);

function PullRequestHeaderDirective() {
    return {
        restrict        : 'E',
        scope           : {
            pull_request_id: '@pullRequestId'
        },
        templateUrl     : 'pull-request/header/header.tpl.html',
        controller      : 'PullRequestHeaderController as pull_request_controller',
        bindToController: true
    };
}
