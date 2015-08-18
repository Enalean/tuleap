(function () {
    angular
        .module('kanban')
        .controller('MainCtrl', MainCtrl);

    MainCtrl.$inject = ['$scope', 'gettextCatalog', 'SharedPropertiesService', 'amMoment'];

    function MainCtrl($scope, gettextCatalog, SharedPropertiesService, amMoment) {
        _.extend($scope, {
            init: init
        });

        function init(kanban, user_id, user_is_admin, lang, project_id, view_mode) {
            SharedPropertiesService.setUserId(user_id);
            SharedPropertiesService.setKanban(kanban);
            SharedPropertiesService.setUserIsAdmin(user_is_admin);
            SharedPropertiesService.setProjectId(project_id);
            SharedPropertiesService.setViewMode(view_mode);
            gettextCatalog.setCurrentLanguage(lang);
            amMoment.changeLocale(lang);
        }
    }
})();
