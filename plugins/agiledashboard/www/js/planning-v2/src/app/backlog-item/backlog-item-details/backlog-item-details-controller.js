import _                  from 'lodash';
import BacklogFilterValue from '../../backlog-filter-terms.js';

export default BacklogItemDetailsController;

BacklogItemDetailsController.$inject = [
    'gettextCatalog',
    'CardFieldsService',
    'EditItemService',
    'BacklogItemService',
    'BacklogItemCollectionService',
    'NewTuleapArtifactModalService'
];

function BacklogItemDetailsController(
    gettextCatalog,
    CardFieldsService,
    EditItemService,
    BacklogItemService,
    BacklogItemCollectionService,
    NewTuleapArtifactModalService
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

        function callback(item_id) {
            return BacklogItemService.removeAddBacklogItemChildren(
                undefined,
                self.backlog_item.id,
                [item_id]
            ).then(() => {
                return appendItemToChildren(item_id, self.backlog_item);
            });
        }

        NewTuleapArtifactModalService.showCreation(
            item_type.id,
            self.backlog_item.id,
            callback
        );
    }

    function appendItemToChildren(child_item_id) {
        return BacklogItemService.getBacklogItem(child_item_id).then(({ backlog_item: child_item }) => {
            child_item.parent = self.backlog_item;
            BacklogItemCollectionService.items[child_item_id] = child_item;

            if (canBeAddedToChildren(child_item_id)) {
                self.backlog_item.children.data.push(child_item);
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
