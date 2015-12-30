angular
    .module('planning')
    .service('BacklogItemCollectionService', BacklogItemCollectionService);

BacklogItemCollectionService.$inject = [
    'BacklogItemService'
];

function BacklogItemCollectionService(
    BacklogItemService
) {
    var self = this;
    _.extend(self, {
        items: {},
        refreshBacklogItem: refreshBacklogItem
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
        });
    }
}
