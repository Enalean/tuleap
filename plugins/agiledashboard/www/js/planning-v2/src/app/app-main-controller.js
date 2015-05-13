(function () {
    angular
        .module('planning')
        .controller('MainCtrl', MainCtrl);

    MainCtrl.$inject = ['$scope', '$window', 'SharedPropertiesService', 'gettextCatalog'];

    function MainCtrl($scope, $window, SharedPropertiesService, gettextCatalog) {
        _.extend($scope, {
            init: init
        });

        function init(project_id, milestone_id, lang, use_angular_new_modal) {
            SharedPropertiesService.setProjectId(project_id);
            SharedPropertiesService.setMilestoneId(milestone_id);
            SharedPropertiesService.setUseAngularNewModal(use_angular_new_modal);
            gettextCatalog.setCurrentLanguage(lang);
            $window.moment.locale(lang);
        }
    }
})();
