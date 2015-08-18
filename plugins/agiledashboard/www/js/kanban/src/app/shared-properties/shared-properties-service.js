(function () {
    angular
        .module('shared-properties')
        .service('SharedPropertiesService', SharedPropertiesService);

    function SharedPropertiesService() {
        var property = {
            user_id      : undefined,
            kanban       : undefined,
            view_mode    : undefined,
            user_is_admin: false,
            project_id   : undefined
        };

        return {
            getUserId     : getUserId,
            setUserId     : setUserId,
            getViewMode   : getViewMode,
            setViewMode   : setViewMode,
            getKanban     : getKanban,
            setKanban     : setKanban,
            getUserIsAdmin: getUserIsAdmin,
            setUserIsAdmin: setUserIsAdmin,
            setProjectId  : setProjectId,
            getProjectId  : getProjectId
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

        function getKanban() {
            return property.kanban;
        }

        function setKanban(kanban) {
            property.kanban = kanban;
        }

        function getUserIsAdmin() {
            return property.user_is_admin;
        }

        function setUserIsAdmin(user_is_admin) {
            property.user_is_admin = user_is_admin;
        }

        function setProjectId(project_id) {
            property.project_id = project_id;
        }

        function getProjectId() {
            return property.project_id;
        }
    }
})();
