import angular from "angular";
import { extend, isBoolean } from "lodash-es";
import { getAccessibilityMode } from "../user-accessibility-mode.js";
export default BacklogItemController;

BacklogItemController.$inject = [
    "$scope",
    "$q",
    "$timeout",
    "$document",
    "$element",
    "BacklogItemService",
    "dragularService",
    "DroppedService",
    "BacklogItemCollectionService",
    "BacklogItemSelectedService",
];

function BacklogItemController(
    $scope,
    $q,
    $timeout,
    $document,
    $element,
    BacklogItemService,
    dragularService,
    DroppedService,
    BacklogItemCollectionService,
    BacklogItemSelectedService,
) {
    var self = this;
    extend(self, {
        BACKLOG_ITEM_CHILDREN_PAGINATION: { limit: 50, offset: 0 },
        backlog_item:
            BacklogItemCollectionService.items[
                $scope.backlog_item.id // inherited from parent scope
            ],
        escaping: false,
        dragular_instance_for_backlog_item_children: undefined,
        children_promise: $q.when(),
        toggleChildrenDisplayed: toggleChildrenDisplayed,
        init: init,
        initDragularForBacklogItemChildren: initDragularForBacklogItemChildren,
        dragularOptionsForBacklogItemChildren: dragularOptionsForBacklogItemChildren,
        moveToBottom: moveToBottom,
        moveToBottomInParent: moveToBottomInParent,
        moveToTop: moveToTop,
        moveToTopInParent: moveToTopInParent,
        reorderBacklogItemChildren: reorderBacklogItemChildren,
        getClassNames: getClassNames,
    });

    self.init();

    function init() {
        $element.on("dragularenter", dragularEnter);
        $element.on("dragularleave", dragularLeave);
        $element.on("dragularrelease", dragularRelease);
        $scope.$on("dragularcancel", dragularCancel);
        $scope.$on("dragulardrop", dragularDrop);
        $scope.$on("dragulardrag", dragularDrag);

        if (!self.backlog_item.children.collapsed) {
            $timeout(function () {
                self.initDragularForBacklogItemChildren();
            });
        }
    }

    function toggleChildrenDisplayed() {
        if (!self.backlog_item.has_children) {
            return;
        }

        if (!self.backlog_item.children.loaded) {
            self.backlog_item.loading = true;
            emptyArray(self.backlog_item.children.data);
            self.children_promise = fetchBacklogItemChildren(
                self.backlog_item,
                self.BACKLOG_ITEM_CHILDREN_PAGINATION.limit,
                self.BACKLOG_ITEM_CHILDREN_PAGINATION.offset,
            );
        }

        self.backlog_item.children.collapsed = !self.backlog_item.children.collapsed;

        $timeout(function () {
            self.initDragularForBacklogItemChildren();
        });
    }

    function fetchBacklogItemChildren(backlog_item, limit, offset) {
        return BacklogItemService.getBacklogItemChildren(backlog_item.id, limit, offset).then(
            function (data) {
                angular.forEach(data.results, function (child) {
                    BacklogItemCollectionService.items[child.id] = child;
                    backlog_item.children.data.push(child);
                });

                if (offset + limit < data.total) {
                    return fetchBacklogItemChildren(backlog_item, limit, offset + limit);
                }
                backlog_item.loading = false;
                backlog_item.children.loaded = true;
            },
        );
    }

    function emptyArray(array) {
        array.length = 0;
    }

    /**
     * Crappy method, but we're forced to manually call parent scope method because it's not inherited
     */
    function moveToTopInParent(backlog_item) {
        if ($scope.$parent.milestoneController) {
            $scope.$parent.milestoneController.moveToTop(backlog_item);
        } else if ($scope.$parent.backlog) {
            $scope.$parent.backlog.moveToTop(backlog_item);
        }
    }

    /**
     * Crappy method, but we're forced to manually call parent scope method because it's not inherited
     */
    function moveToBottomInParent(backlog_item) {
        if ($scope.$parent.milestoneController) {
            $scope.$parent.milestoneController.moveToBottom(backlog_item);
        } else if ($scope.$parent.backlog) {
            $scope.$parent.backlog.moveToBottom(backlog_item);
        }
    }

    function moveToTop(backlog_item) {
        var moved_items = [backlog_item],
            compared_to;

        if (BacklogItemSelectedService.areThereMultipleSelectedBaklogItems()) {
            moved_items = BacklogItemSelectedService.getCompactedSelectedBacklogItem();
        }

        compared_to = DroppedService.defineComparedToBeFirstItem(
            self.backlog_item.children.data,
            moved_items,
        );

        self.reorderBacklogItemChildren(self.backlog_item.id, moved_items, compared_to);
    }

    function moveToBottom(backlog_item) {
        var moved_items = [backlog_item],
            compared_to;

        if (backlog_item.moving_to) {
            return;
        }

        if (BacklogItemSelectedService.areThereMultipleSelectedBaklogItems()) {
            moved_items = BacklogItemSelectedService.getCompactedSelectedBacklogItem();
        }

        backlog_item.moving_to = true;

        self.children_promise.then(function () {
            compared_to = DroppedService.defineComparedToBeLastItem(
                self.backlog_item.children.data,
                moved_items,
            );
            backlog_item.moving_to = false;

            self.reorderBacklogItemChildren(self.backlog_item.id, moved_items, compared_to);
        });
    }

    function initDragularForBacklogItemChildren() {
        var backlog_item_element = angular.element(
            '.backlog-item-children-list[data-backlog-item-id="' + self.backlog_item.id + '"]',
        );

        self.dragular_instance_for_backlog_item_children = dragularService(
            backlog_item_element,
            self.dragularOptionsForBacklogItemChildren(),
        );

        $document.on("keyup", function (event) {
            var esc_key_code = 27;

            if (event.keyCode === esc_key_code) {
                BacklogItemSelectedService.deselectAllBacklogItems();

                self.escaping = true;
                self.dragular_instance_for_backlog_item_children.cancel(true);

                $scope.$apply();
            }
        });
    }

    function dragularOptionsForBacklogItemChildren() {
        return {
            containersModel: self.backlog_item.children.data,
            scope: $scope,
            revertOnSpill: true,
            nameSpace: "dragular-list-children",
            accepts: isItemDroppable,
            moves: isItemDraggable,
        };
    }

    function dragularEnter(event) {
        if ($element[0] === event.delegateTarget) {
            var dropped_item_element = dragularService.shared.item,
                source_list_element = angular.element(dragularService.shared.source),
                target_element = event.currentTarget,
                target_list_element = getListElement(target_element);

            if (
                !canDropIntoElement(source_list_element, target_list_element, dropped_item_element)
            ) {
                return;
            }

            $element.addClass("appending-child");
        }
    }

    function dragularLeave(event) {
        if (!event.delegateTarget.contains(dragularService.shared.extra)) {
            $element.removeClass("appending-child");
        }
    }

    function dragularRelease() {
        $element.removeClass("appending-child");
    }

    function dragularDrag(event, element) {
        event.stopPropagation();

        if (
            BacklogItemSelectedService.areThereMultipleSelectedBaklogItems() &&
            BacklogItemSelectedService.isDraggedBacklogItemSelected(getDroppedItemId(element))
        ) {
            BacklogItemSelectedService.multipleBacklogItemsAreDragged(element);
        } else {
            BacklogItemSelectedService.deselectAllBacklogItems();
        }

        $scope.$apply();
    }

    function dragularCancel(event, dropped_item_element, source_element) {
        event.stopPropagation();

        if (self.escaping) {
            self.escaping = false;
            return;
        }

        var target_element = angular.element(dragularService.shared.extra),
            source_list_element = angular.element(source_element);

        if (isBoolean(target_element[0])) {
            // dragular sets extra to true if we just drop the element at its original place
            BacklogItemSelectedService.deselectAllBacklogItems();
            $scope.$apply();

            return;
        }

        var backlog_item_element = target_element.closest(".backlog-item"),
            target_list_element = getListElement(backlog_item_element);

        if (!canDropIntoElement(source_list_element, target_list_element, dropped_item_element)) {
            return;
        }

        var dropped_item_ids = [getDroppedItemId(dropped_item_element)],
            source_backlog_item = self.backlog_item,
            target_backlog_item = target_list_element.scope().backlog_item,
            target_backlog_item_id = getBacklogItemId(target_list_element),
            initial_index = dragularService.shared.initialIndex;

        var dropped_items = [source_backlog_item.children.data[initial_index]];

        if (BacklogItemSelectedService.areThereMultipleSelectedBaklogItems()) {
            dropped_items = BacklogItemSelectedService.getCompactedSelectedBacklogItem();
            dropped_item_ids = dropped_items.map((dropped_item) => dropped_item.id);
        }

        // the dropped element must be removed for ngRepeat to apply correctly.
        // see dragular's source code at https://github.com/luckylooke/dragular
        source_element.removeChild(dropped_item_element);

        var child_item = pluckDroppedItemFromSourceBacklogItem(source_backlog_item, initial_index);
        target_backlog_item.children.data.unshift(child_item);
        target_backlog_item.has_children = true;

        dropChildToAnotherParentItem(
            source_backlog_item.id,
            target_backlog_item_id,
            dropped_item_ids,
            dropped_items,
            null,
        )
            .then(function () {
                BacklogItemSelectedService.deselectAllBacklogItems();
            })
            .catch(function () {
                BacklogItemSelectedService.reselectBacklogItems();
            });
    }

    function dragularDrop(
        event,
        dropped_item_element,
        target_element,
        source_element,
        source_model,
        initial_index,
        target_model,
        target_index,
    ) {
        event.stopPropagation();

        var source_list_element = angular.element(source_element),
            target_list_element = angular.element(target_element),
            dropped_item_ids = [getDroppedItemId(dropped_item_element)];

        if (!target_model) {
            target_model = source_model;
        }

        var dropped_items = [target_model[target_index]];

        if (BacklogItemSelectedService.areThereMultipleSelectedBaklogItems()) {
            dropped_items = BacklogItemSelectedService.getCompactedSelectedBacklogItem();
            dropped_item_ids = dropped_items.map((dropped_item) => dropped_item.id);
        }

        var compared_to = DroppedService.defineComparedTo(
            target_model,
            target_model[target_index],
            dropped_items,
        );

        saveChangesInBackend();
        BacklogItemSelectedService.deselectAllBacklogItems();

        function saveChangesInBackend() {
            switch (true) {
                case droppedToSameParentItem(source_list_element, target_list_element):
                    var current_backlog_item_id = getBacklogItemId(target_list_element);

                    self.reorderBacklogItemChildren(
                        current_backlog_item_id,
                        dropped_items,
                        compared_to,
                    );
                    break;

                case droppedToAnotherParentItem(source_list_element, target_list_element):
                    var source_backlog_item_id = getBacklogItemId(source_list_element),
                        target_backlog_item_id = getBacklogItemId(target_list_element);

                    dropChildToAnotherParentItem(
                        source_backlog_item_id,
                        target_backlog_item_id,
                        dropped_item_ids,
                        dropped_items,
                        compared_to,
                    )
                        .then(function () {
                            BacklogItemSelectedService.deselectAllBacklogItems();
                        })
                        .catch(function () {
                            BacklogItemSelectedService.reselectBacklogItems();
                        });
                    break;
            }
        }
    }

    function reorderBacklogItemChildren(backlog_item_id, children, compared_to) {
        BacklogItemCollectionService.addOrReorderBacklogItemsInCollection(
            self.backlog_item.children.data,
            children,
            compared_to,
        );

        return DroppedService.reorderBacklogItemChildren(
            children.map((child) => child.id),
            compared_to,
            backlog_item_id,
        )
            .then(function () {
                BacklogItemSelectedService.deselectAllBacklogItems();
            })
            .catch(function () {
                BacklogItemSelectedService.reselectBacklogItems();
            });
    }

    function getListElement(element) {
        return angular.element(element).find(".backlog-item-children-list").last();
    }

    function canDropIntoElement(source_element, target_element, dropped_item_element) {
        return (
            target_element.length === 1 &&
            getBacklogItemId(source_element) !== getBacklogItemId(target_element) &&
            isItemDroppable(dropped_item_element, target_element)
        );
    }

    function pluckDroppedItemFromSourceBacklogItem(source_backlog_item, initial_index) {
        var child_item = source_backlog_item.children.data.splice(initial_index, 1);

        if (source_backlog_item.children.data.length === 0) {
            source_backlog_item.has_children = false;
            source_backlog_item.children.collapsed = true;
        }

        return child_item[0];
    }

    function droppedToSameParentItem(source_list_element, target_list_element) {
        return getBacklogItemId(source_list_element) === getBacklogItemId(target_list_element);
    }

    function droppedToAnotherParentItem(source_list_element, target_list_element) {
        return isABacklogItem(source_list_element) && isABacklogItem(target_list_element);
    }

    function dropChildToAnotherParentItem(
        source_backlog_item_id,
        target_backlog_item_id,
        dropped_item_ids,
        dropped_items,
        compared_to,
    ) {
        BacklogItemCollectionService.items[source_backlog_item_id].updating = true;
        BacklogItemCollectionService.items[target_backlog_item_id].updating = true;

        BacklogItemCollectionService.removeBacklogItemsFromCollection(
            BacklogItemCollectionService.items[source_backlog_item_id].children.data,
            dropped_items,
        );
        BacklogItemCollectionService.addOrReorderBacklogItemsInCollection(
            BacklogItemCollectionService.items[target_backlog_item_id].children.data,
            dropped_items,
            compared_to,
        );

        var promise = DroppedService.moveFromChildrenToChildren(
            dropped_item_ids,
            compared_to,
            source_backlog_item_id,
            target_backlog_item_id,
        ).then(function () {
            BacklogItemCollectionService.refreshBacklogItem(source_backlog_item_id);
            BacklogItemCollectionService.refreshBacklogItem(target_backlog_item_id);
        });

        return promise;
    }

    function getBacklogItemId(backlog_item_element) {
        return backlog_item_element.data("backlog-item-id");
    }

    function getDroppedItemId(dropped_item) {
        return angular.element(dropped_item).data("item-id");
    }

    function isABacklogItem(element) {
        return element.hasClass("backlog-item-children-list");
    }

    function isItemDroppable(element_to_drop, target_container_element) {
        var target_container = angular.element(target_container_element);

        if (target_container.data("nodrop")) {
            return false;
        }

        var accepted = target_container.data("accept").split("|"),
            type = angular.element(element_to_drop).data("type");

        return accepted.includes(type);
    }

    function isItemDraggable(element_to_drag, container, handle_element) {
        return !ancestorCannotBeDragged(handle_element) && ancestorHasHandleClass(handle_element);
    }

    function ancestorHasHandleClass(handle_element) {
        return angular.element(handle_element).closest(".dragular-handle-child").length > 0;
    }

    function ancestorCannotBeDragged(handle_element) {
        return (
            angular
                .element(handle_element)
                .parentsUntil(".backlog-item-children-list")
                .addBack()
                .filter('[data-nodrag="true"]').length > 0
        );
    }

    function getClassNames(backlog_item) {
        return [
            "backlog-item-color-" + backlog_item.color,
            "tlp-swatch-" + backlog_item.background_color_name,
            backlog_item.status.toLowerCase(),
            backlog_item.moving_to ? "moving-to" : "",
            backlog_item.created ? "created" : "",
            backlog_item.updated ? "updated" : "",
            backlog_item.updating ? "updating" : "",
            backlog_item.selected ? "selected" : "",
            backlog_item.multiple ? "multiple" : "",
            backlog_item.hidden ? "hidden" : "",
            backlog_item.shaking ? "shaking" : "",
            getAccessibilityMode() && backlog_item.background_color_name
                ? "accessibility-displayed"
                : "",
        ];
    }
}
