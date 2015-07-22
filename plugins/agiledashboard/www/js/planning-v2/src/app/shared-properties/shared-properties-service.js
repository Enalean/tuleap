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
            use_angular_new_modal: undefined
        };

        return {
            getUserId            : getUserId,
            setUserId            : setUserId,
            getViewMode          : getViewMode,
            setViewMode          : setViewMode,
            getProjectId         : getProjectId,
            setProjectId         : setProjectId,
            getMilestoneId       : getMilestoneId,
            setMilestoneId       : setMilestoneId,
            getUseAngularNewModal: getUseAngularNewModal,
            setUseAngularNewModal: setUseAngularNewModal
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
    }
})();
