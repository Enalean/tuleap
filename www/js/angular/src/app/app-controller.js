angular
    .module('tuleap.pull-request')
    .controller('MainController', MainController);

MainController.$inject = [
    '$scope',
    'SharedPropertiesService',
    'gettextCatalog'
];

/* eslint-disable angular/controller-as */
function MainController(
    $scope,
    SharedPropertiesService,
    gettextCatalog
) {
    $scope.init = init;

    function init(repository_id, user_id, language) {
        SharedPropertiesService.setRepositoryId(repository_id);
        SharedPropertiesService.setUserId(user_id);

        initLocale(language);
    }

    function initLocale(language) {
        gettextCatalog.setCurrentLanguage(language);
    }
}
