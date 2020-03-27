export default DroppedService;

DroppedService.$inject = ["KanbanService"];

function DroppedService(KanbanService) {
    var self = this;

    self.getComparedTo = getComparedTo;
    self.getComparedToBeFirstItemOfColumn = getComparedToBeFirstItemOfColumn;
    self.getComparedToBeLastItemOfColumn = getComparedToBeLastItemOfColumn;
    self.moveToColumn = moveToColumn;
    self.reorderColumn = reorderColumn;

    function reorderColumn(kanban_id, column_id, dropped_item_id, compared_to) {
        if (column_id === "archive") {
            return KanbanService.reorderArchive(kanban_id, dropped_item_id, compared_to);
        } else if (column_id === "backlog") {
            return KanbanService.reorderBacklog(kanban_id, dropped_item_id, compared_to);
        }

        return KanbanService.reorderColumn(kanban_id, column_id, dropped_item_id, compared_to);
    }

    function moveToColumn(kanban_id, column_id, dropped_item_id, compared_to, from_column) {
        if (column_id === "archive") {
            return KanbanService.moveInArchive(
                kanban_id,
                dropped_item_id,
                compared_to,
                from_column
            );
        }
        if (column_id === "backlog") {
            return KanbanService.moveInBacklog(
                kanban_id,
                dropped_item_id,
                compared_to,
                from_column
            );
        }

        return KanbanService.moveInColumn(
            kanban_id,
            column_id,
            dropped_item_id,
            compared_to,
            from_column
        );
    }

    function getComparedTo(item_list, index) {
        var compared_to = {};

        if (item_list.length === 1) {
            return null;
        }

        if (index === 0) {
            compared_to.direction = "before";
            compared_to.item_id = item_list[index + 1].id;
        } else {
            compared_to.direction = "after";
            compared_to.item_id = item_list[index - 1].id;
        }

        return compared_to;
    }

    function getComparedToBeFirstItemOfColumn(column) {
        if (column.content.length === 0) {
            return null;
        }

        var first_item = column.content[0];
        var compared_to = {
            direction: "before",
            item_id: first_item.id,
        };

        return compared_to;
    }

    function getComparedToBeLastItemOfColumn(column) {
        if (column.content.length === 0) {
            return null;
        }

        var last_item = column.content[column.content.length - 1];
        var compared_to = {
            direction: "after",
            item_id: last_item.id,
        };

        return compared_to;
    }
}
