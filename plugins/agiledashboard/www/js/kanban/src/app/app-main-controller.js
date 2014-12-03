(function () {
    angular
        .module('kanban')
        .controller('MainCtrl', MainCtrl);

    MainCtrl.$inject = ['$scope', 'gettextCatalog', 'SharedPropertiesService'];

    function MainCtrl($scope, gettextCatalog, SharedPropertiesService) {
        _.extend($scope, {
            init: init
        });

        function init(name, lang) {
            SharedPropertiesService.setName(name);
            gettextCatalog.setCurrentLanguage(lang);
        }
    }
})();