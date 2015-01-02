(function () {
    angular
        .module('kanban')
        .controller('KanbanCtrl', KanbanCtrl);

    KanbanCtrl.$inject = [
        'SharedPropertiesService',
        'KanbanService',
        'CardFieldsService'
    ];

    function KanbanCtrl(SharedPropertiesService, KanbanService, CardFieldsService) {
        var self   = this,
            limit  = 10,
            offset = 0,
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
        self.loading_backlog = true;
        self.cardFieldIsSimpleValue       = CardFieldsService.cardFieldIsSimpleValue;
        self.cardFieldIsList              = CardFieldsService.cardFieldIsList;
        self.cardFieldIsText              = CardFieldsService.cardFieldIsText;
        self.cardFieldIsDate              = CardFieldsService.cardFieldIsDate;
        self.cardFieldIsFile              = CardFieldsService.cardFieldIsFile;
        self.cardFieldIsCross             = CardFieldsService.cardFieldIsCross;
        self.cardFieldIsPermissions       = CardFieldsService.cardFieldIsPermissions;
        self.getCardFieldListValues       = CardFieldsService.getCardFieldListValues;
        self.getCardFieldTextValue        = CardFieldsService.getCardFieldTextValue;
        self.getCardFieldFileValue        = CardFieldsService.getCardFieldFileValue;
        self.getCardFieldCrossValue       = CardFieldsService.getCardFieldCrossValue;
        self.getCardFieldPermissionsValue = CardFieldsService.getCardFieldPermissionsValue;

        loadColumns();
        loadBacklog(limit, offset);

        function loadColumns() {
            KanbanService.getKanban(kanban.tracker_id).then(function (kanban) {
                self.board.columns = kanban.columns;
            });
        }

        function loadBacklog(limit, offset) {
            return KanbanService.getBacklog(kanban.tracker_id, limit, offset).then(function(data) {
                self.backlog.content = self.backlog.content.concat(data.results);

                if (self.backlog.content.length < data.total) {
                    loadBacklog(limit, offset + limit);
                } else {
                    self.loading_backlog = false;
                }
            });
        }
    }
})();
