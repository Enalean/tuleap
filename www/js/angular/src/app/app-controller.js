angular
    .module('tuleap.pull-request')
    .controller('MainController', MainController);

MainController.$inject = [
    '$scope',
    'gettextCatalog',
    'amMoment',
    'SharedPropertiesService'
];

/* eslint-disable angular/controller-as */
function MainController(
    $scope,
    gettextCatalog,
    amMoment,
    SharedPropertiesService
) {
    $scope.init = init;

    function init(
        repository_id,
        user_id,
        language,
        nb_pull_request_badge,
        is_there_at_least_one_pull_request
    ) {
        SharedPropertiesService.setRepositoryId(repository_id);
        SharedPropertiesService.setUserId(user_id);
        SharedPropertiesService.setNbPullRequestBadge(nb_pull_request_badge);
        SharedPropertiesService.setIsThereAtLeastOnePullRequest(is_there_at_least_one_pull_request);

        initLocale(language);
    }

    function initLocale(language) {
        gettextCatalog.setCurrentLanguage(language);
        amMoment.changeLocale(language);
    }
}
