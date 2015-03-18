(function () {
    angular
        .module('shared-properties')
        .service('SharedPropertiesService', SharedPropertiesService);

    function SharedPropertiesService() {
        var property = {
            kanban: undefined,
            user_is_admin: false
        };

        return {
            getKanban: getKanban,
            setKanban: setKanban,
            getUserIsAdmin: getUserIsAdmin,
            setUserIsAdmin: setUserIsAdmin
        };

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
    }
})();
