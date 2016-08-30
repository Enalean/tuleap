angular
    .module('tuleap.pull-request')
    .controller('MainController', MainController);

MainController.$inject = [
    'lodash',
    '$scope',
    '$state',
    'PullRequestsService',
    'SharedPropertiesService',
    'gettextCatalog',
    'amMoment'
];

/* eslint-disable angular/controller-as */
function MainController(
    _,
    $scope,
    $state,
    PullRequestsService,
    SharedPropertiesService,
    gettextCatalog,
    amMoment
) {
    $scope.init = init;

    function init(repository_id, user_id, language) {
        SharedPropertiesService.setRepositoryId(repository_id);
        SharedPropertiesService.setUserId(user_id);

        initLocale(language);

        var dataPromise = loadData(repository_id);
        SharedPropertiesService.setReadyPromise(dataPromise);
        dataPromise.then(function() {
            redirectToOverview();
        });
    }

    function initLocale(language) {
        gettextCatalog.setCurrentLanguage(language);
        amMoment.changeLocale(language);
    }

    function loadData(repository_id) {
        return PullRequestsService.getPullRequests(
                repository_id,
                PullRequestsService.pull_requests_pagination.limit,
                PullRequestsService.pull_requests_pagination.offset)
            .then(function(pull_requests) {
                var wanted_pull_request_id = ($state.includes('pull-request')) ? parseInt($state.params.id, 10) : pull_requests[0].id;
                SharedPropertiesService.setPullRequest(_.find(pull_requests, { id: wanted_pull_request_id }));
            });
    }

    function redirectToOverview() {
        if (_.includes(['pull-requests', 'pull-request'], $state.current.name)) {
            var pull_request_id = SharedPropertiesService.getPullRequest().id;
            $state.go('overview', { id: pull_request_id });
        }
    }
}
