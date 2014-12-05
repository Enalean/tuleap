(function () {
    angular
        .module('planning')
        .controller('MainCtrl', MainCtrl);

    MainCtrl.$inject = ['$scope', '$window', 'SharedPropertiesService', 'gettextCatalog'];

    function MainCtrl($scope, $window, SharedPropertiesService, gettextCatalog) {
        _.extend($scope, {
            init: init
        });

        function init(project_id, milestone_id, lang) {
            SharedPropertiesService.setProjectId(project_id);
            SharedPropertiesService.setMilestoneId(milestone_id);
            gettextCatalog.setCurrentLanguage(lang);
            $window.moment.locale(lang);
        }
    }
})();