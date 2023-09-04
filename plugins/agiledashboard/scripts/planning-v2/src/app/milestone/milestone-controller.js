import angular from "angular";

export default MilestoneController;

MilestoneController.$inject = [
    "$scope",
    "$q",
    "$timeout",
    "$document",
    "dragularService",
    "BacklogService",
    "DroppedService",
    "MilestoneCollectionService",
    "BacklogItemSelectedService",
    "SharedPropertiesService",
];

function MilestoneController(
    $scope,
    $q,
    $timeout,
    $document,
    dragularService,
    BacklogService,
    DroppedService,
    MilestoneCollectionService,
    BacklogItemSelectedService,
    SharedPropertiesService,
) {
    const self = this;
    Object.assign(self, {
        canUserMoveCards,
        dragularOptionsForMilestone,
        init,
        initDragularForMilestone,
        isCollapsed,
        isExpanded,
        isMilestoneLoadedAndEmpty,
        moveToBottom,
        moveToTop,
        reorderMilestoneContent,
        toggleMilestone,
        milestone: $scope.milestone, // inherited from parent scope
        dragular_instance_for_milestone: undefined,
        get_content_promise: $q.when(),
        additionalPanesToDisplay,
        hasExternalParent: hasOriginalProject,
    });

    self.init();

    function init() {
        $scope.$on("dragularcancel", dragularCancel);
        $scope.$on("dragulardrop", dragularDrop);
        $scope.$on("dragulardrag", dragularDrag);
    }

    function toggleMilestone() {
        if (!self.milestone.alreadyLoaded && self.milestone.content.length === 0) {
            self.get_content_promise = self.milestone.getContent();
        }

        self.milestone.collapsed = !self.milestone.collapsed;

        if (!self.dragular_instance_for_milestone) {
            $timeout(function () {
                self.initDragularForMilestone();
            });
        }
    }

    function hasOriginalProject() {
        return self.milestone.original_project_provider !== null;
    }

    function isCollapsed() {
        return !self.milestone.updating && self.milestone.collapsed;
    }

    function isExpanded() {
        return !self.milestone.updating && !self.milestone.collapsed;
    }

    function isMilestoneLoadedAndEmpty() {
        return !self.milestone.loadingContent && self.milestone.content.length === 0;
    }

    function canUserMoveCards() {
        return self.milestone.has_user_priority_change_permission;
    }

    function moveToTop(backlog_item) {
        var moved_items = [backlog_item],
            compared_to;

        if (BacklogItemSelectedService.areThereMultipleSelectedBaklogItems()) {
            moved_items = BacklogItemSelectedService.getCompactedSelectedBacklogItem();
        }

        compared_to = DroppedService.defineComparedToBeFirstItem(
            self.milestone.content,
            moved_items,
        );

        self.reorderMilestoneContent(self.milestone.id, moved_items, compared_to);
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

        self.get_content_promise.then(function () {
            compared_to = DroppedService.defineComparedToBeLastItem(
                self.milestone.content,
                moved_items,
            );

            backlog_item.moving_to = false;

            self.reorderMilestoneContent(self.milestone.id, moved_items, compared_to);
        });
    }

    function initDragularForMilestone() {
        var milestone_element = angular.element(
            'div.submilestone[data-submilestone-id="' + self.milestone.id + '"]',
        );

        self.dragular_instance_for_milestone = dragularService(
            milestone_element,
            self.dragularOptionsForMilestone(),
        );

        $document.bind("keyup", function (event) {
            var esc_key_code = 27;

            if (event.keyCode === esc_key_code) {
                BacklogItemSelectedService.deselectAllBacklogItems();

                self.dragular_instance_for_milestone.cancel(true);

                $scope.$apply();
            }
        });
    }

    function dragularOptionsForMilestone() {
        return {
            containersModel: self.milestone.content,
            scope: $scope,
            revertOnSpill: true,
            nameSpace: "dragular-list",
            accepts: isItemDroppable,
            moves: isItemDraggable,
        };
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

    function dragularCancel(event) {
        event.stopPropagation();

        BacklogItemSelectedService.deselectAllBacklogItems();
        $scope.$apply();
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
            dropped_item_ids = [getDroppedItemId(dropped_item_element)],
            current_milestone_id = getMilestoneId(target_list_element);

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

        function saveChangesInBackend() {
            var source_milestone_id = getMilestoneId(source_list_element);

            switch (true) {
                case droppedToSameMilestone(source_list_element, target_list_element):
                    self.reorderMilestoneContent(current_milestone_id, dropped_items, compared_to);
                    break;

                case droppedToAnotherMilestone(source_list_element, target_list_element):
                    var target_milestone_id = getMilestoneId(target_list_element);

                    MilestoneCollectionService.removeBacklogItemsFromMilestoneContent(
                        source_milestone_id,
                        dropped_items,
                    );
                    MilestoneCollectionService.addOrReorderBacklogItemsInMilestoneContent(
                        target_milestone_id,
                        dropped_items,
                        compared_to,
                    );

                    DroppedService.moveFromSubmilestoneToSubmilestone(
                        dropped_item_ids,
                        compared_to,
                        source_milestone_id,
                        target_milestone_id,
                    )
                        .then(function () {
                            BacklogItemSelectedService.deselectAllBacklogItems();
                            MilestoneCollectionService.refreshMilestone(source_milestone_id);
                            MilestoneCollectionService.refreshMilestone(target_milestone_id);
                        })
                        .catch(function () {
                            BacklogItemSelectedService.reselectBacklogItems();
                        });
                    break;

                case droppedToBacklog(target_list_element):
                    MilestoneCollectionService.removeBacklogItemsFromMilestoneContent(
                        source_milestone_id,
                        dropped_items,
                    );
                    BacklogService.addOrReorderBacklogItemsInBacklog(dropped_items, compared_to);

                    DroppedService.moveFromSubmilestoneToBacklog(
                        dropped_item_ids,
                        compared_to,
                        source_milestone_id,
                        BacklogService.backlog,
                    )
                        .then(function () {
                            BacklogItemSelectedService.deselectAllBacklogItems();
                            MilestoneCollectionService.refreshMilestone(source_milestone_id);
                        })
                        .catch(function () {
                            BacklogItemSelectedService.reselectBacklogItems();
                        });
                    break;
            }
        }
    }

    function reorderMilestoneContent(milestone_id, backlog_items, compared_to) {
        MilestoneCollectionService.addOrReorderBacklogItemsInMilestoneContent(
            milestone_id,
            backlog_items,
            compared_to,
        );

        return DroppedService.reorderSubmilestone(
            backlog_items.map((backlog_item) => backlog_item.id),
            compared_to,
            milestone_id,
        )
            .then(function () {
                BacklogItemSelectedService.deselectAllBacklogItems();
            })
            .catch(function () {
                BacklogItemSelectedService.reselectBacklogItems();
            });
    }

    function droppedToSameMilestone(source_list_element, target_list_element) {
        return getMilestoneId(source_list_element) === getMilestoneId(target_list_element);
    }

    function droppedToAnotherMilestone(source_list_element, target_list_element) {
        return isAMilestone(source_list_element) && isAMilestone(target_list_element);
    }

    function droppedToBacklog(target_list_element) {
        return isBacklog(target_list_element);
    }

    function isAMilestone(element) {
        return element.hasClass("submilestone");
    }

    function isBacklog(element) {
        return element.hasClass("backlog");
    }

    function getMilestoneId(milestone_element) {
        return milestone_element.data("submilestone-id");
    }

    function getDroppedItemId(dropped_item) {
        return angular.element(dropped_item).data("item-id");
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
        return angular.element(handle_element).closest(".dragular-handle").length > 0;
    }

    function ancestorCannotBeDragged(handle_element) {
        return angular.element(handle_element).closest('[data-nodrag="true"]').length > 0;
    }

    function additionalPanesToDisplay() {
        return self.milestone.resources.additional_panes.filter((pane) =>
            SharedPropertiesService.getAllowedAdditionalPanesToDisplay().includes(pane.identifier),
        );
    }
}
