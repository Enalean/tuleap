(function () {
    angular
        .module('kanban')
        .controller('MainCtrl', MainCtrl);

    MainCtrl.$inject = ['$scope', 'gettextCatalog'];

    function MainCtrl($scope, gettextCatalog) {
        _.extend($scope, {
            init: init
        });

        function init(lang) {
            gettextCatalog.setCurrentLanguage(lang);
        }
    }
})();