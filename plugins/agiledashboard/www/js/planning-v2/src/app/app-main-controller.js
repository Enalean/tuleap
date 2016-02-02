(function () {
    angular
        .module('planning')
        .controller('MainCtrl', MainCtrl);

    MainCtrl.$inject = ['$scope', '$window', 'SharedPropertiesService', 'gettextCatalog'];

    function MainCtrl($scope, $window, SharedPropertiesService, gettextCatalog) {
        _.extend($scope, {
            init: init
        });

        function init(user_id, project_id, milestone_id, lang, view_mode, milestone, initial_backlog_items, initial_milestones) {
            SharedPropertiesService.setUserId(user_id);
            SharedPropertiesService.setProjectId(project_id);
            SharedPropertiesService.setMilestoneId(milestone_id);
            SharedPropertiesService.setMilestone(milestone);
            SharedPropertiesService.setInitialBacklogItems(initial_backlog_items);
            SharedPropertiesService.setInitialMilestones(initial_milestones);
            SharedPropertiesService.setViewMode(view_mode);

            initLocale(lang);
        }

        function initLocale(lang) {
            gettextCatalog.setCurrentLanguage(lang);
            $window.moment.locale(lang);
        }
    }
})();
