import {
    remove,
    some,
    find
} from 'lodash';

export default BacklogItemCollectionService;

BacklogItemCollectionService.$inject = [
    'BacklogItemService',
    'ItemAnimatorService'
];

function BacklogItemCollectionService(
    BacklogItemService,
    ItemAnimatorService
) {
    const self = this;
    Object.assign(self, {
        items: {},
        refreshBacklogItem,
        removeBacklogItemsFromCollection,
        addOrReorderBacklogItemsInCollection
    });

    function refreshBacklogItem(backlog_item_id) {
        self.items[backlog_item_id].updating = true;

        return BacklogItemService.getBacklogItem(backlog_item_id).then(({ backlog_item }) => {
            const {
                background_color_name,
                label,
                initial_effort,
                remaining_effort,
                card_fields,
                status,
                has_children,
                parent
            } = backlog_item;

            Object.assign(self.items[backlog_item_id], {
                background_color_name,
                label,
                initial_effort,
                remaining_effort,
                card_fields,
                updating: false,
                status,
                has_children,
                parent
            });

            ItemAnimatorService.animateUpdated(self.items[backlog_item_id]);

            if (! backlog_item.has_children) {
                self.items[backlog_item_id].children.collapsed = true;
            }
        });
    }

    function removeBacklogItemsFromCollection(backlog_items_collection, backlog_items_to_remove) {
        remove(backlog_items_collection, function(item) {
            return some(backlog_items_to_remove, item);
        });
    }

    function addOrReorderBacklogItemsInCollection(backlog_items_collection, backlog_items_to_add_or_reorder, compared_to) {
        var index = 0;

        self.removeBacklogItemsFromCollection(backlog_items_collection, backlog_items_to_add_or_reorder);

        if (compared_to) {
            index = backlog_items_collection.indexOf(
                find(backlog_items_collection, { id: compared_to.item_id })
            );

            if (compared_to.direction === 'after') {
                index = index + 1;
            }
        }

        var args = [index, 0].concat(backlog_items_to_add_or_reorder);
        Array.prototype.splice.apply(backlog_items_collection, args);
    }
}
