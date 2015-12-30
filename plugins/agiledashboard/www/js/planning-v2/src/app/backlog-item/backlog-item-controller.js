angular
    .module('backlog-item')
    .controller('BacklogItemController', BacklogItemController);

BacklogItemController.$inject = [
    'BacklogItemService',
    'CardFieldsService',
    'BacklogItemCollectionService',
    'NewTuleapArtifactModalService'
];

function BacklogItemController(
    BacklogItemService,
    CardFieldsService,
    BacklogItemCollectionService,
    NewTuleapArtifactModalService
) {
    var self = this;
    _.extend(self, {
        BACKLOG_ITEM_CHILDREN_PAGINATION: { limit: 50, offset: 0 },
        canBeAddedToBacklogItemChildren: canBeAddedToBacklogItemChildren,
        showAddChildModal              : showAddChildModal,
        toggleChildrenDisplayed        : toggleChildrenDisplayed,
        cardFieldIsCross            : CardFieldsService.cardFieldIsCross,
        cardFieldIsDate             : CardFieldsService.cardFieldIsDate,
        cardFieldIsFile             : CardFieldsService.cardFieldIsFile,
        cardFieldIsList             : CardFieldsService.cardFieldIsList,
        cardFieldIsPermissions      : CardFieldsService.cardFieldIsPermissions,
        cardFieldIsSimpleValue      : CardFieldsService.cardFieldIsSimpleValue,
        cardFieldIsText             : CardFieldsService.cardFieldIsText,
        cardFieldIsUser             : CardFieldsService.cardFieldIsUser,
        getCardFieldCrossValue      : CardFieldsService.getCardFieldCrossValue,
        getCardFieldFileValue       : CardFieldsService.getCardFieldFileValue,
        getCardFieldListValues      : CardFieldsService.getCardFieldListValues,
        getCardFieldPermissionsValue: CardFieldsService.getCardFieldPermissionsValue,
        getCardFieldTextValue       : CardFieldsService.getCardFieldTextValue,
        getCardFieldUserValue       : CardFieldsService.getCardFieldUserValue
    });

    function toggleChildrenDisplayed(scope, backlog_item) {
        scope.toggle();

        if (backlog_item.has_children && ! backlog_item.children.loaded) {
            backlog_item.loading = true;
            backlog_item.children.data = [];
            fetchBacklogItemChildren(backlog_item, self.BACKLOG_ITEM_CHILDREN_PAGINATION.limit, self.BACKLOG_ITEM_CHILDREN_PAGINATION.offset);
        }
    }

    function fetchBacklogItemChildren(backlog_item, limit, offset) {
        return BacklogItemService.getBacklogItemChildren(backlog_item.id, limit, offset).then(function(data) {
            angular.forEach(data.results, function(child) {
                BacklogItemCollectionService.items[child.id] = child;
                backlog_item.children.data.push(child);
            });

            if ((offset + limit) < data.total) {
                fetchBacklogItemChildren(backlog_item, limit, offset + limit);
            } else {
                backlog_item.loading         = false;
                backlog_item.children.loaded = true;
            }
        });
    }

    function showAddChildModal($event, item_type, parent_item) {
        $event.preventDefault();

        var callback = function(item_id) {
            var promise = BacklogItemService.removeAddBacklogItemChildren(undefined, parent_item.id, item_id);

            promise.then(function() {
                return appendItemToBacklogItem(item_id, parent_item);
            });

            return promise;
        };

        NewTuleapArtifactModalService.showCreation(item_type.id, parent_item, callback);
    }

    function appendItemToBacklogItem(child_item_id, parent_item) {
        return BacklogItemService.getBacklogItem(child_item_id).then(function(data) {
            BacklogItemCollectionService.items[child_item_id] = data.backlog_item;

            if (canBeAddedToBacklogItemChildren(child_item_id, parent_item)) {
                parent_item.children.data.push(data.backlog_item);
                if (! parent_item.has_children) {
                    parent_item.children.loaded = true;
                }
            }
            BacklogItemCollectionService.refreshBacklogItem(parent_item.id);
        });
    }

    function canBeAddedToBacklogItemChildren(child_item_id, parent_item) {
        if (! parent_item.has_children) {
            return true;
        }

        if (parent_item.has_children && parent_item.children.loaded) {
            var child_already_in_children = _.find(parent_item.children.data, { id: child_item_id });

            if (child_already_in_children === undefined) {
                return true;
            }
        }

        return false;
    }
}
