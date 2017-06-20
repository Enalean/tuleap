angular
    .module('backlog-item-details')
    .controller('BacklogItemDetailsController', BacklogItemDetailsController);

BacklogItemDetailsController.$inject = [
    'gettextCatalog',
    'CardFieldsService',
    'EditItemService',
    'BacklogItemService',
    'BacklogItemCollectionService',
    'NewTuleapArtifactModalService',
    'BacklogFilterValue'
];

function BacklogItemDetailsController(
    gettextCatalog,
    CardFieldsService,
    EditItemService,
    BacklogItemService,
    BacklogItemCollectionService,
    NewTuleapArtifactModalService,
    BacklogFilterValue
) {
    var self = this;

    _.extend(self, {
        backlog_filter                       : BacklogFilterValue,
        canBeAddedToChildren                 : canBeAddedToChildren,
        cardFieldIsCross                     : CardFieldsService.cardFieldIsCross,
        cardFieldIsDate                      : CardFieldsService.cardFieldIsDate,
        cardFieldIsFile                      : CardFieldsService.cardFieldIsFile,
        cardFieldIsList                      : CardFieldsService.cardFieldIsList,
        cardFieldIsPermissions               : CardFieldsService.cardFieldIsPermissions,
        cardFieldIsSimpleValue               : CardFieldsService.cardFieldIsSimpleValue,
        cardFieldIsText                      : CardFieldsService.cardFieldIsText,
        cardFieldIsUser                      : CardFieldsService.cardFieldIsUser,
        cardFieldIsComputed                  : CardFieldsService.cardFieldIsComputed,
        getCardFieldCrossValue               : CardFieldsService.getCardFieldCrossValue,
        getCardFieldDateValue                : CardFieldsService.getCardFieldDateValue,
        getCardFieldFileValue                : CardFieldsService.getCardFieldFileValue,
        getCardFieldListValues               : CardFieldsService.getCardFieldListValues,
        getCardFieldPermissionsValue         : CardFieldsService.getCardFieldPermissionsValue,
        getCardFieldTextValue                : CardFieldsService.getCardFieldTextValue,
        getCardFieldUserValue                : CardFieldsService.getCardFieldUserValue,
        isListBoundToAValueDifferentFromNone : CardFieldsService.isListBoundToAValueDifferentFromNone,
        getInitialEffortMessage              : getInitialEffortMessage,
        showAddChildModal                    : showAddChildModal,
        showEditModal                        : EditItemService.showEditModal
    });

    function getInitialEffortMessage(initial_effort) {
        return gettextCatalog.getPlural(initial_effort, "pt", "pts");
    }

    function showAddChildModal($event, item_type) {
        $event.preventDefault();

        var callback = function(item_id) {
            var promise = BacklogItemService.removeAddBacklogItemChildren(
                undefined,
                self.backlog_item.id,
                [item_id]
            );

            promise.then(function() {
                return appendItemToChildren(item_id, self.backlog_item);
            });

            return promise;
        };

        NewTuleapArtifactModalService.showCreation(
            item_type.id,
            self.backlog_item,
            callback
        );
    }

    function appendItemToChildren(child_item_id) {
        return BacklogItemService.getBacklogItem(child_item_id).then(function(data) {
            BacklogItemCollectionService.items[child_item_id] = data.backlog_item;

            if (canBeAddedToChildren(child_item_id)) {
                self.backlog_item.children.data.push(data.backlog_item);
                if (! self.backlog_item.has_children) {
                    self.backlog_item.children.loaded = true;
                }
            }
            BacklogItemCollectionService.refreshBacklogItem(self.backlog_item.id);
        });
    }

    function canBeAddedToChildren(child_item_id) {
        if (! self.backlog_item.has_children) {
            return true;
        }

        if (! self.backlog_item.children.loaded) {
            return false;
        }

        var child_already_in_children = _.find(self.backlog_item.children.data, { id: child_item_id });

        return angular.isUndefined(child_already_in_children);
    }
}
