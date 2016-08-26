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

    function init(repository_id, user_id, language) {
        SharedPropertiesService.setRepositoryId(repository_id);
        SharedPropertiesService.setUserId(user_id);

        initLocale(language);
    }

    function initLocale(language) {
        gettextCatalog.setCurrentLanguage(language);
        amMoment.changeLocale(language);
    }
}
