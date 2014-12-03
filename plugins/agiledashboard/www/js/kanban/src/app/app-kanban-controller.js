(function () {
    angular
        .module('kanban')
        .controller('KanbanCtrl', KanbanCtrl);

    KanbanCtrl.$inject = ['SharedPropertiesService'];

    function KanbanCtrl(SharedPropertiesService) {
        this.name = SharedPropertiesService.getName();
    }
})();