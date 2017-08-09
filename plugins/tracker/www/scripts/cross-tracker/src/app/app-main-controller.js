import moment from 'moment';

export default MainController;

MainController.$inject = [
    '$scope',
    'gettextCatalog',
    'SharedPropertiesService'
];

function MainController(
    $scope,
    gettextCatalog,
    SharedPropertiesService
) {
    $scope.init = init;

    function init(language, user_id) {
        SharedPropertiesService.setUserId(user_id);
        gettextCatalog.setCurrentLanguage(language);
        moment.locale(language);
    }
}
