import _ from "lodash";

export default ColumnCollectionService;

ColumnCollectionService.$inject = ["SharedPropertiesService"];

function ColumnCollectionService(SharedPropertiesService) {
    var self = this;
    _.extend(self, {
        cancelWipEditionOnAllColumns: cancelWipEditionOnAllColumns,
        getColumn: getColumn,
        getBoardColumn: getBoardColumn,
        augmentColumn: augmentColumn,
        getColumns: getColumns,
        addColumn: addColumn,
        removeColumn: removeColumn,
        reorderColumns: reorderColumns,
        findItemById: findItemById
    });

    function getColumn(id) {
        if (id === "archive") {
            return SharedPropertiesService.getKanban().archive;
        } else if (id === "backlog") {
            return SharedPropertiesService.getKanban().backlog;
        } else if (id) {
            return getBoardColumn(id);
        }

        return null;
    }

    function getColumnIndex(column_id) {
        return _.findIndex(getColumns(), { id: column_id });
    }

    function getBoardColumn(id) {
        return _.find(SharedPropertiesService.getKanban().columns, { id: id });
    }

    function getColumns() {
        return SharedPropertiesService.getKanban().columns;
    }

    function reorderColumns(columns_ids) {
        var columns = SharedPropertiesService.getKanban().columns;
        _.forEach(columns, function(column_to_replace, index) {
            if (!columns_ids[index]) {
                return;
            }
            if (columns_ids[index] !== column_to_replace.id) {
                var index_column_tmp = getColumnIndex(columns_ids[index]);
                columns[index_column_tmp] = columns.splice(
                    index,
                    1,
                    getColumn(columns_ids[index])
                )[0];
            }
        });
    }

    function cancelWipEditionOnAllColumns() {
        _.forEach(SharedPropertiesService.getKanban().columns, function(column) {
            column.wip_in_edit = false;
        });
    }

    function addColumn(new_column) {
        augmentColumn(new_column);
        new_column.is_defered = false;
        new_column.loading_items = false;

        getColumns().push(new_column);
    }

    function removeColumn(column_id) {
        var column_to_remove = getColumn(column_id);

        if (column_to_remove) {
            _.remove(getColumns(), { id: column_id });
        }
    }

    function augmentColumn(column) {
        column.content = [];
        column.filtered_content = [];
        column.loading_items = true;
        column.nb_items_at_kanban_init = 0;
        column.fully_loaded = false;
        column.wip_in_edit = false;
        column.limit_input = column.limit;
        column.saving_wip = false;
        column.is_defered = !column.is_open;
        column.original_label = column.label;
    }

    function findItemById(item_id) {
        var item;

        _.find(getColumns(), function(column) {
            item = findItemInColumnById(item_id, column);

            if (item) {
                return item;
            }
        });

        if (!item) {
            item = findItemInColumnById(item_id, getColumn("backlog"));
        }

        if (!item) {
            item = findItemInColumnById(item_id, getColumn("archive"));
        }

        if (!item) {
            item = null;
        }

        return item;
    }

    function findItemInColumnById(item_id, column) {
        return _.find(column.content, { id: item_id });
    }
}
