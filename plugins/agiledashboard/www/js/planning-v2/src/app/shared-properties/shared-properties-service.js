(function () {
    angular
        .module('shared-properties')
        .service('SharedPropertiesService', SharedPropertiesService);

    function SharedPropertiesService() {
        var property = {
            user_id              : undefined,
            view_mode            : undefined,
            project_id           : undefined,
            milestone_id         : undefined,
            use_angular_new_modal: undefined,
            milestone            : undefined,
            initial_backlog_items: undefined,
            initial_milestones   : undefined
        };

        return {
            getUserId             : getUserId,
            setUserId             : setUserId,
            getViewMode           : getViewMode,
            setViewMode           : setViewMode,
            getProjectId          : getProjectId,
            setProjectId          : setProjectId,
            getMilestoneId        : getMilestoneId,
            setMilestoneId        : setMilestoneId,
            getUseAngularNewModal : getUseAngularNewModal,
            setUseAngularNewModal : setUseAngularNewModal,
            getMilestone          : getMilestone,
            setMilestone          : setMilestone,
            getInitialBacklogItems: getInitialBacklogItems,
            setInitialBacklogItems: setInitialBacklogItems,
            getInitialMilestones  : getInitialMilestones,
            setInitialMilestones  : setInitialMilestones,
        };

        function getUserId() {
            return property.user_id;
        }

        function setUserId(user_id) {
            property.user_id = user_id;
        }

        function getViewMode() {
            return property.view_mode;
        }

        function setViewMode(view_mode) {
            property.view_mode = view_mode;
        }

        function getProjectId() {
            return property.project_id;
        }

        function setProjectId(project_id) {
            property.project_id = project_id;
        }

        function getMilestoneId() {
            return property.milestone_id;
        }

        function setMilestoneId(milestone_id) {
            property.milestone_id = milestone_id;
        }

        function getUseAngularNewModal() {
            return property.use_angular_new_modal;
        }

        function setUseAngularNewModal(use_angular_new_modal) {
            property.use_angular_new_modal = use_angular_new_modal;
        }

        function getMilestone() {
            return property.milestone;
        }

        function setMilestone(milestone) {
            property.milestone = milestone;
        }

        function getInitialBacklogItems() {
            return property.initial_backlog_items;
        }

        function setInitialBacklogItems(initial_backlog_items) {
            property.initial_backlog_items = initial_backlog_items;
        }

        function getInitialMilestones() {
            return property.initial_milestones;
        }

        function setInitialMilestones(initial_milestones) {
            property.initial_milestones = initial_milestones;
        }

    }
})();
