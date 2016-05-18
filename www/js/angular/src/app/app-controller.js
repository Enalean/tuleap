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
    lodash,
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
            .then(function(pullRequests) {
                var wantedPullRequestId = ($state.includes('pull-request')) ? parseInt($state.params.id, 10) : pullRequests[0].id;
                SharedPropertiesService.setPullRequest(lodash.find(pullRequests, { id: wantedPullRequestId }));
            });
    }

    function redirectToOverview() {
        if (lodash.includes(['pull-requests', 'pull-request'], $state.current.name)) {
            var prId = SharedPropertiesService.getPullRequest().id;
            $state.go('overview', { id: prId });
        }
    }
}
