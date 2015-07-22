(function () {
    angular
        .module('planning')
        .controller('MainCtrl', MainCtrl);

    MainCtrl.$inject = ['$scope', '$window', 'SharedPropertiesService', 'gettextCatalog'];

    function MainCtrl($scope, $window, SharedPropertiesService, gettextCatalog) {
        _.extend($scope, {
            init: init
        });

        function init(user_id, project_id, milestone_id, lang, use_angular_new_modal, view_mode) {
            SharedPropertiesService.setUserId(user_id);
            SharedPropertiesService.setProjectId(project_id);
            SharedPropertiesService.setMilestoneId(milestone_id);
            SharedPropertiesService.setUseAngularNewModal(use_angular_new_modal);
            SharedPropertiesService.setViewMode(view_mode);
            gettextCatalog.setCurrentLanguage(lang);
            $window.moment.locale(lang);
        }
    }
})();
