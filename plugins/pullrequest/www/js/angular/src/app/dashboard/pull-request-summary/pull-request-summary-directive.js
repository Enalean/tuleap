angular
    .module('tuleap.pull-request')
    .directive('pullRequestSummary', PullRequestSummaryDirective);

function PullRequestSummaryDirective() {
    return {
        restrict: 'AE',
        scope   : {
            pull_request: '=pullRequestData'
        },
        templateUrl     : 'dashboard/pull-request-summary/pull-request-summary.tpl.html',
        controller      : 'PullRequestSummaryController as summary_controller',
        bindToController: true
    };
}
