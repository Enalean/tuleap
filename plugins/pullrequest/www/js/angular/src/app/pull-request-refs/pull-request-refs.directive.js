angular
    .module('tuleap.pull-request')
    .directive('pullRequestRefs', PullRequestRefsDirective);

function PullRequestRefsDirective() {
    return {
        restrict: 'AE',
        scope   : {
            pull_request: '=pullRequestData'
        },
        templateUrl     : 'pull-request-refs/pull-request-refs.tpl.html',
        controller      : 'PullRequestRefsController as refs_controller',
        bindToController: true
    };
}

