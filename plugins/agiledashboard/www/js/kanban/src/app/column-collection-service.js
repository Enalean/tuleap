angular
    .module('kanban')
    .service('ColumnCollectionService', ColumnCollectionService);

ColumnCollectionService.$inject = [
    'SharedPropertiesService'
];

function ColumnCollectionService(
    SharedPropertiesService
) {
    var self = this;
    _.extend(self, {
        cancelWipEditionOnAllColumns: cancelWipEditionOnAllColumns,
        getColumn                   : getColumn
    });

    function getColumn(id) {
        if (id === 'archive') {
            return SharedPropertiesService.getKanban().archive;
        } else if (id === 'backlog') {
            return SharedPropertiesService.getKanban().backlog;
        } else if (id) {
            return getBoardColumn(id);
        }

        return null;
    }

    function getBoardColumn(id) {
        return _.find(SharedPropertiesService.getKanban().columns, { id: id });
    }

    function cancelWipEditionOnAllColumns() {
        _.forEach(SharedPropertiesService.getKanban().columns, function(column) {
            column.wip_in_edit = false;
        });
    }
}
