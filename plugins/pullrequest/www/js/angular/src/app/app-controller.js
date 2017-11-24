angular
    .module('tuleap.pull-request')
    .controller('MainController', MainController);

MainController.$inject = [
    '$element',
    'gettextCatalog',
    'amMoment',
    'SharedPropertiesService'
];

/* eslint-disable angular/controller-as */
function MainController(
    $element,
    gettextCatalog,
    amMoment,
    SharedPropertiesService
) {
    init();

    function init() {
        const pullrequest_init_data = $element[0].querySelector('.pullrequest-init-data').dataset;

        const repository_id = pullrequest_init_data.repositoryId;
        SharedPropertiesService.setRepositoryId(repository_id);
        const user_id = pullrequest_init_data.userId;
        SharedPropertiesService.setUserId(user_id);
        const nb_pull_request_badge = pullrequest_init_data.nbPullRequestBadge;
        SharedPropertiesService.setNbPullRequestBadge(nb_pull_request_badge);
        const is_there_at_least_one_pull_request = pullrequest_init_data.isThereAtLeastOnePullRequest;
        SharedPropertiesService.setIsThereAtLeastOnePullRequest(is_there_at_least_one_pull_request);

        const language = pullrequest_init_data.language;
        initLocale(language);
    }

    function initLocale(language) {
        gettextCatalog.setCurrentLanguage(language);
        amMoment.changeLocale(language);
    }
}
