(function () {
    angular
        .module('kanban')
        .controller('KanbanCtrl', KanbanCtrl);

    KanbanCtrl.$inject = ['SharedPropertiesService', 'KanbanService'];

    function KanbanCtrl(SharedPropertiesService, KanbanService) {
        var self = this,
            kanban = SharedPropertiesService.getKanban();

        self.name      = kanban.name;
        self.nb_open   = kanban.nb_open;
        self.nb_closed = kanban.nb_closed;
        self.board = {
            columns: []
        };
        self.backlog = {
            content: [],
            label: 'Backlog',
            is_open: false
        };
        self.archive = {
            content: [],
            label: 'Closed',
            is_open: false
        };

        loadColumns();

        function loadColumns() {
            KanbanService.getKanban(kanban.tracker_id).then(function (kanban) {
                self.board.columns = kanban.columns;
            });
        }
    }
})();
