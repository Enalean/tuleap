(function () {
    angular
        .module('kanban')
        .controller('MainCtrl', MainCtrl);

    MainCtrl.$inject = ['$scope', 'gettextCatalog', 'SharedPropertiesService', 'amMoment'];

    function MainCtrl($scope, gettextCatalog, SharedPropertiesService, amMoment) {
        _.extend($scope, {
            init: init
        });

        function init(kanban, user_is_admin, lang) {
            SharedPropertiesService.setKanban(kanban);
            SharedPropertiesService.setUserIsAdmin(user_is_admin);
            gettextCatalog.setCurrentLanguage(lang);
            amMoment.changeLocale(lang);
        }
    }
})();
