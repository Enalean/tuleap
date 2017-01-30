angular
    .module('kanban')
    .service('KanbanColumnService', KanbanColumnService);

KanbanColumnService.$inject = [
    '$q',
    '$filter',
    'KanbanFilterValue',
    'KanbanItemRestService',
    'SharedPropertiesService'
];

function KanbanColumnService(
    $q,
    $filter,
    KanbanFilterValue,
    KanbanItemRestService,
    SharedPropertiesService
) {
    var self = this;
    _.extend(self, {
        addItem             : addItem,
        updateItemContent   : updateItemContent,
        filterItems         : filterItems,
        moveItem            : moveItem,
        findAndMoveItem     : findAndMoveItem
    });

    function moveItem(
        item,
        source_column,
        destination_column,
        compared_to
    ) {
        removeItem(item, source_column);
        if (hasColumnChanged(item, destination_column.id)) {
            updateItem(item, destination_column.id);
        }
        removeItemFromFilteredContent(item, destination_column);
        addItem(item, destination_column, compared_to);
    }

    function findAndMoveItem(
        id,
        source_column,
        destination_column,
        compared_to
    ) {
        var promised_item = findItemInColumnById(id, source_column);

        if (! promised_item) {
            promised_item = KanbanItemRestService.getItem(id).then(function(item) {
                if (! item) {
                    return;
                }

                item.is_collapsed = SharedPropertiesService.doesUserPrefersCompactCards();
                return item;
            });
        }

        $q.when(promised_item).then(function(item) {
            if (! item) {
                return;
            }

            item.updating = false;

            self.moveItem(
                item,
                source_column,
                destination_column,
                compared_to
            );

            if (destination_column.is_open) {
                self.filterItems(destination_column);
            }
        });
    }

    function findItemInColumnById(item_id, column) {
        return _.find(column.content, {'id': item_id});
    }

    function removeItem(item, column) {
        if (column.fully_loaded) {
            _.remove(column.content, { id: item.id });
            if (column.is_open) {
                removeItemFromFilteredContent(item, column);
            }
        } else {
            column.nb_items_at_kanban_init--;
        }
    }

    function removeItemFromFilteredContent(item, column) {
        _.remove(column.filtered_content, { id: item.id });
    }

    function addItem(item, column, compared_to) {
        if (column.fully_loaded) {
            addItemInCollectionComparedToAnotherItem(item, column.content, compared_to);
            addItemInCollectionComparedToAnotherItem(item, column.filtered_content, compared_to);
        } else {
            column.nb_items_at_kanban_init++;
        }

        if (! column.is_open) {
            emptyArray(column.filtered_content);
        }
    }

    function hasColumnChanged(item, destination_column_id) {
        return item.in_column !== destination_column_id;
    }

    function updateItemContent(item, item_updated) {
        var updated_item = _.pick(item_updated, function (value, key) {
            return _.contains([
                'color',
                'item_name',
                'label',
                'card_fields'
            ], key);
        });

        _.extend(item, updated_item);

        updateItem(item, item_updated.in_column);
    }

    function updateItem(item, destination_column_id) {
        if (movedFromBacklogToAnotherColumn(item.in_column, destination_column_id)) {
            updateTimeInfo(item, 'kanban');
        }
        updateTimeInfo(item, destination_column_id);
        updateItemColumn(item, destination_column_id);
    }

    function movedFromBacklogToAnotherColumn(source_column_id, destination_column_id) {
        return source_column_id === 'backlog' && destination_column_id !== 'backlog';
    }

    function updateItemColumn(item, column_id) {
        item.in_column = column_id;
    }

    function updateTimeInfo(item, column_id) {
        item.timeinfo[column_id] = new Date();
    }

    function addItemInCollectionComparedToAnotherItem(item, collection, compared_to) {
        var compared_to_index = -1;
        if (! _.isNull(compared_to)) {
            compared_to_index = _.findIndex(collection, {
                id: compared_to.item_id
            });
        }

        if (compared_to_index !== -1) {
            if (compared_to.direction === 'after') {
                collection.splice(compared_to_index + 1, 0, item);
            } else {
                collection.splice(compared_to_index, 0, item);
            }
        } else {
            collection.push(item);
        }
    }

    function filterItems(column) {
        var filtered_items = $filter('InPropertiesFilter')(column.content, KanbanFilterValue.terms);

        emptyArray(column.filtered_content);
        _.forEach(filtered_items, function(item) {
            column.filtered_content.push(item);
        });
    }

    function emptyArray(array) {
        array.length = 0;
    }
}
