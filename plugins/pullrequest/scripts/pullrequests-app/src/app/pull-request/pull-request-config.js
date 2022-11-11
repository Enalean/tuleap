export default PullRequestConfig;

PullRequestConfig.$inject = ["$stateProvider"];

function PullRequestConfig($stateProvider) {
    $stateProvider.state("pull-request", {
        url: "/pull-requests/{id:[0-9]+}",
        template: '<div pull-request id="pull-request"></div>',
    });
}
