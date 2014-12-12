(function () {
    angular
        .module('kanban')
        .controller('KanbanCtrl', KanbanCtrl);

    KanbanCtrl.$inject = ['SharedPropertiesService'];

    function KanbanCtrl(SharedPropertiesService) {
        var kanban = SharedPropertiesService.getKanban();

        this.name      = kanban.name;
        this.nb_open   = kanban.nb_open;
        this.nb_closed = kanban.nb_closed;
        this.board = {
            columns: [
                {
                    id: 123,
                    content: [],
                    label: 'To be plannified',
                    is_open: true,
                    limit: null
                },
                {
                    id: 234,
                    content: [],
                    label: 'On going',
                    is_open: true,
                    limit: 3
                },
                {
                    id: 345,
                    content: [],
                    label: 'To test',
                    is_open: true,
                    limit: 3
                },
                {
                    id: 456,
                    content: [],
                    label: 'Blocked',
                    is_open: true,
                    limit: 9
                }
            ]
        };
        this.backlog = {
            content: [],
            label: 'Backlog',
            is_open: false
        };
        this.archive = {
            content: [],
            label: 'Closed',
            is_open: false
        };
    }
})();
