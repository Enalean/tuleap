import angular from "angular";
import _ from "lodash";
import BacklogFilterValue from "../../backlog-filter-terms.js";
import { getAccessibilityMode } from "../../user-accessibility-mode.js";

export default BacklogItemDetailsController;

BacklogItemDetailsController.$inject = [
    "gettextCatalog",
    "EditItemService",
    "BacklogItemService",
    "BacklogItemCollectionService",
    "NewTuleapArtifactModalService",
    "ItemAnimatorService"
];

function BacklogItemDetailsController(
    gettextCatalog,
    EditItemService,
    BacklogItemService,
    BacklogItemCollectionService,
    NewTuleapArtifactModalService,
    ItemAnimatorService
) {
    const self = this;
    Object.assign(self, {
        user_has_accessibility_mode: getAccessibilityMode(),
        backlog_filter: BacklogFilterValue,
        showEditModal: EditItemService.showEditModal,
        showAddChildModal,
        canBeAddedToChildren,
        getCardColorName
    });

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

        NewTuleapArtifactModalService.showCreation(item_type.id, self.backlog_item.id, callback);
    }

    function appendItemToChildren(child_item_id) {
        return BacklogItemService.getBacklogItem(child_item_id).then(
            ({ backlog_item: child_item }) => {
                child_item.parent = self.backlog_item;
                ItemAnimatorService.animateCreated(child_item);
                BacklogItemCollectionService.items[child_item_id] = child_item;

                if (canBeAddedToChildren(child_item_id)) {
                    self.backlog_item.children.data.push(child_item);
                    if (!self.backlog_item.has_children) {
                        self.backlog_item.children.loaded = true;
                    }
                }
                BacklogItemCollectionService.refreshBacklogItem(self.backlog_item.id);
            }
        );
    }

    function canBeAddedToChildren(child_item_id) {
        if (!self.backlog_item.has_children) {
            return true;
        }

        if (!self.backlog_item.children.loaded) {
            return false;
        }

        var child_already_in_children = _.find(self.backlog_item.children.data, {
            id: child_item_id
        });

        return angular.isUndefined(child_already_in_children);
    }

    function getCardColorName() {
        return self.backlog_item.background_color_name
            ? self.backlog_item.background_color_name
            : self.backlog_item.color;
    }
}
