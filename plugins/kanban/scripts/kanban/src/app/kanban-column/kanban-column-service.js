import "../in-properties-filter/in-properties-filter.js";
import { remove } from "lodash-es";

export default KanbanColumnService;

KanbanColumnService.$inject = [
    "$q",
    "$filter",
    "KanbanFilterValue",
    "KanbanItemRestService",
    "SharedPropertiesService",
];

function KanbanColumnService(
    $q,
    $filter,
    KanbanFilterValue,
    KanbanItemRestService,
    SharedPropertiesService,
) {
    const self = this;
    Object.assign(self, {
        addItem,
        updateItemContent,
        filterItems,
        moveItem,
        findItemAndReorderItems,
        findItemAndReorderItemMercure,
    });

    function moveItem(item, source_column, destination_column, compared_to) {
        removeItem(item, source_column);
        if (hasColumnChanged(item, destination_column.id)) {
            updateItem(item, destination_column.id);
        }
        removeItemFromFilteredContent(item, destination_column);
        addItem(item, destination_column, compared_to);
    }

    function findItem(id, source_column) {
        var promised_item = findItemInColumnById(id, source_column);

        if (!promised_item && source_column.is_open) {
            return null;
        }

        if (!promised_item) {
            promised_item = KanbanItemRestService.getItem(id).then(function (item) {
                if (!item) {
                    return null;
                }

                item.is_collapsed = SharedPropertiesService.doesUserPrefersCompactCards();
                return item;
            });
        }
        return promised_item;
    }

    function findItemAndReorderItems(
        id,
        source_column,
        destination_column,
        ordered_destination_column_items_ids,
    ) {
        const promised_item = findItem(id, source_column);

        $q.when(promised_item).then(function (item) {
            if (!item) {
                return;
            }

            item.updating = false;

            self.moveItem(item, source_column, destination_column, null);

            reorderItemsByItemsIds(ordered_destination_column_items_ids, destination_column);

            if (destination_column.is_open) {
                self.filterItems(destination_column);
            }
        });
    }

    function findItemAndReorderItemMercure(
        id,
        source_column,
        destination_column,
        ordered_destination_column_item_ids,
    ) {
        const item = findItemInColumnById(id, source_column);
        if (!item) {
            return;
        }
        item.updating = false;
        self.moveItem(item, source_column, destination_column, null);
        reorderItemsByItemsIds(ordered_destination_column_item_ids, destination_column);
        if (destination_column.is_open) {
            self.filterItems(destination_column);
        }
    }

    function findItemInColumnById(item_id, column) {
        return column.content.find(({ id }) => id === item_id);
    }

    function removeItem(item, column) {
        if (column.fully_loaded) {
            remove(column.content, { id: item.id });
            if (column.is_open) {
                removeItemFromFilteredContent(item, column);
            }
        } else {
            column.nb_items_at_kanban_init--;
        }
    }

    function removeItemFromFilteredContent(item, column) {
        remove(column.filtered_content, { id: item.id });
    }

    function addItem(item, column, compared_to) {
        if (column.fully_loaded) {
            addItemInCollectionComparedToAnotherItem(item, column.content, compared_to);
            addItemInCollectionComparedToAnotherItem(item, column.filtered_content, compared_to);
        } else {
            column.nb_items_at_kanban_init++;
        }

        if (!column.is_open) {
            emptyArray(column.filtered_content);
        }
    }

    function hasColumnChanged(item, destination_column_id) {
        return item.in_column !== destination_column_id;
    }

    function updateItemContent(item, item_updated) {
        const { background_color_name, color, item_name, label, card_fields } = item_updated;
        Object.assign(item, {
            background_color_name,
            color,
            item_name,
            label,
            card_fields,
        });

        updateItem(item, item_updated.in_column);
    }

    function updateItem(item, destination_column_id) {
        if (movedFromBacklogToAnotherColumn(item.in_column, destination_column_id)) {
            updateTimeInfo(item, "kanban");
        }
        updateTimeInfo(item, destination_column_id);
        updateItemColumn(item, destination_column_id);
    }

    function movedFromBacklogToAnotherColumn(source_column_id, destination_column_id) {
        return source_column_id === "backlog" && destination_column_id !== "backlog";
    }

    function updateItemColumn(item, column_id) {
        item.in_column = column_id;
    }

    function updateTimeInfo(item, column_id) {
        item.timeinfo[column_id] = new Date();
    }

    function addItemInCollectionComparedToAnotherItem(item, collection, compared_to) {
        var compared_to_index = -1;
        if (compared_to !== null) {
            compared_to_index = collection.findIndex(({ id }) => id === compared_to.item_id);
        }

        if (compared_to_index !== -1) {
            if (compared_to.direction === "after") {
                collection.splice(compared_to_index + 1, 0, item);
            } else {
                collection.splice(compared_to_index, 0, item);
            }
        } else {
            collection.push(item);
        }
    }

    function reorderItemsByItemsIds(ordered_destination_column_items_ids, column) {
        const content = [];
        ordered_destination_column_items_ids.forEach((item_id) => {
            const item = column.content.find((item) => item.id === item_id);
            if (item) {
                content.push(item);
            }
        });
        column.content = content;
    }

    function filterItems(column) {
        var filtered_items = $filter("InPropertiesFilter")(column.content, KanbanFilterValue.terms);

        emptyArray(column.filtered_content);
        filtered_items.forEach((item) => {
            column.filtered_content.push(item);
        });
    }

    function emptyArray(array) {
        array.length = 0;
    }
}
