(function () {
    angular
        .module('kanban')
        .controller('MainCtrl', MainCtrl);

    MainCtrl.$inject = ['$scope', 'gettextCatalog', 'SharedPropertiesService'];

    function MainCtrl($scope, gettextCatalog, SharedPropertiesService) {
        _.extend($scope, {
            init: init
        });

        function init(kanban, lang) {
            SharedPropertiesService.setKanban(kanban);
            gettextCatalog.setCurrentLanguage(lang);
        }
    }
})();
