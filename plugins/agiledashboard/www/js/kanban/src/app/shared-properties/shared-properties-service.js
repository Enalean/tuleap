(function () {
    angular
        .module('shared-properties')
        .service('SharedPropertiesService', SharedPropertiesService);

    function SharedPropertiesService() {
        var property = {
            kanban: undefined
        };

        return {
            getKanban: getKanban,
            setKanban: setKanban
        };

        function getKanban() {
            return property.kanban;
        }

        function setKanban(kanban) {
            property.kanban = kanban;
        }
    }
})();
