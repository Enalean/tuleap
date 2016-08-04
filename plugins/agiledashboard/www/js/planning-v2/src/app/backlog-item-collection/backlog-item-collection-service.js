angular
    .module('backlog-item-collection')
    .service('BacklogItemCollectionService', BacklogItemCollectionService);

BacklogItemCollectionService.$inject = [
    'BacklogItemService'
];

function BacklogItemCollectionService(
    BacklogItemService
) {
    var self = this;
    _.extend(self, {
        items                               : {},
        refreshBacklogItem                  : refreshBacklogItem,
        removeBacklogItemsFromCollection    : removeBacklogItemsFromCollection,
        addOrReorderBacklogItemsInCollection: addOrReorderBacklogItemsInCollection
    });

    function refreshBacklogItem(backlog_item_id) {
        self.items[backlog_item_id].updating = true;

        return BacklogItemService.getBacklogItem(backlog_item_id).then(function(data) {
            self.items[backlog_item_id].label          = data.backlog_item.label;
            self.items[backlog_item_id].initial_effort = data.backlog_item.initial_effort;
            self.items[backlog_item_id].card_fields    = data.backlog_item.card_fields;
            self.items[backlog_item_id].updating       = false;
            self.items[backlog_item_id].status         = data.backlog_item.status;
            self.items[backlog_item_id].has_children   = data.backlog_item.has_children;

            if (! data.backlog_item.has_children) {
                self.items[backlog_item_id].children.collapsed = true;
            }
        });
    }

    function removeBacklogItemsFromCollection(backlog_items_collection, backlog_items_to_remove) {
        _.remove(backlog_items_collection, function(item) {
            return _.some(backlog_items_to_remove, item);
        });
    }

    function addOrReorderBacklogItemsInCollection(backlog_items_collection, backlog_items_to_add_or_reorder, compared_to) {
        var index = 0;

        self.removeBacklogItemsFromCollection(backlog_items_collection, backlog_items_to_add_or_reorder);

        if (compared_to) {
            index = backlog_items_collection.indexOf(
                _.find(backlog_items_collection, { id: compared_to.item_id })
            );

            if (compared_to.direction === 'after') {
                index = index + 1;
            }
        }

        var args = [index, 0].concat(backlog_items_to_add_or_reorder);
        Array.prototype.splice.apply(backlog_items_collection, args);
    }
}
