import { remove } from "lodash-es";

export default ColumnCollectionService;

ColumnCollectionService.$inject = ["SharedPropertiesService"];

function ColumnCollectionService(SharedPropertiesService) {
    var self = this;
    Object.assign(self, {
        cancelWipEditionOnAllColumns: cancelWipEditionOnAllColumns,
        getColumn: getColumn,
        getBoardColumn: getBoardColumn,
        augmentColumn: augmentColumn,
        getColumns: getColumns,
        addColumn: addColumn,
        removeColumn: removeColumn,
        reorderColumns: reorderColumns,
        findItemById: findItemById,
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
        return getColumns().findIndex(({ id }) => id === column_id);
    }

    function getBoardColumn(column_id) {
        return SharedPropertiesService.getKanban().columns.find(({ id }) => id === column_id);
    }

    function getColumns() {
        return SharedPropertiesService.getKanban().columns;
    }

    function reorderColumns(columns_ids) {
        const columns = SharedPropertiesService.getKanban().columns;
        columns.forEach((column_to_replace, index) => {
            if (!columns_ids[index]) {
                return;
            }
            if (columns_ids[index] !== column_to_replace.id) {
                var index_column_tmp = getColumnIndex(columns_ids[index]);
                columns[index_column_tmp] = columns.splice(
                    index,
                    1,
                    getColumn(columns_ids[index]),
                )[0];
            }
        });
    }

    function cancelWipEditionOnAllColumns() {
        SharedPropertiesService.getKanban().columns.forEach((column) => {
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
            remove(getColumns(), { id: column_id });
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
        let item;

        for (const column of getColumns()) {
            item = findItemInColumnById(item_id, column);
            if (item) {
                return item;
            }
        }

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
        return column.content.find(({ id }) => id === item_id);
    }
}
