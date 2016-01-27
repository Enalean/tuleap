angular
    .module('backlog')
    .controller('BacklogController', BacklogController);

BacklogController.$inject = [
    '$scope',
    '$document',
    'dragularService',
    'BacklogService',
    'DroppedService',
    'MilestoneCollectionService',
    'BacklogItemSelectedService'
];

function BacklogController(
    $scope,
    $document,
    dragularService,
    BacklogService,
    DroppedService,
    MilestoneCollectionService,
    BacklogItemSelectedService
) {
    var self = this;
    _.extend(self, {
        details                      : BacklogService.backlog,
        items                        : BacklogService.items,
        dragular_instance_for_backlog: undefined,
        canUserMoveCards         : BacklogService.canUserMoveCards,
        displayUserCantPrioritize: displayUserCantPrioritize,
        dragularOptionsForBacklog: dragularOptionsForBacklog,
        isBacklogLoadedAndEmpty  : isBacklogLoadedAndEmpty
    });

    initDragular();

    function initDragular() {
        self.dragular_instance_for_backlog = dragularService(document.querySelector('ul.backlog'), self.dragularOptionsForBacklog());

        $scope.$on('dragulardrop', dragularDrop);
        $scope.$on('dragularcancel', dragularCancel);
        $scope.$on('dragulardrag', dragularDrag);

        $document.bind('keyup', function(event) {
            var esc_key_code = 27;

            if (event.keyCode === esc_key_code) {
                BacklogItemSelectedService.deselectAllBacklogItems();

                self.dragular_instance_for_backlog.cancel(true);

                $scope.$apply();
            }
        });
    }

    function dragularOptionsForBacklog() {
        return {
            containersModel: self.items.filtered_content,
            scope          : $scope,
            revertOnSpill  : true,
            nameSpace      : 'dragular-list',
            accepts        : isItemDroppable,
            moves          : isItemDraggable
        };
    }

    function hideUserCantPrioritize() {
        return BacklogService.backlog.user_can_move_cards || BacklogService.items.content.length === 0;
    }

    function displayUserCantPrioritize() {
        return ! hideUserCantPrioritize();
    }

    function isBacklogLoadedAndEmpty() {
        return ! BacklogService.items.loading &&
            BacklogService.items.content.length === 0;
    }

    function dragularDrag(event, element) {
        event.stopPropagation();

        if (BacklogItemSelectedService.areThereMultipleSelectedBaklogItems() &&
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
        target_index
    ) {
        event.stopPropagation();

        var source_list_element = angular.element(source_element),
            target_list_element = angular.element(target_element),
            dropped_item_ids    = [getDroppedItemId(dropped_item_element)];

        if (! target_model) {
            target_model = source_model;
        }

        var dropped_items = [target_model[target_index]];

        if (BacklogItemSelectedService.areThereMultipleSelectedBaklogItems()) {
            dropped_items    = BacklogItemSelectedService.getCompactedSelectedBacklogItem();
            dropped_item_ids = _.pluck(dropped_items, 'id');
        }

        var compared_to = DroppedService.defineComparedTo(target_model, target_model[target_index], dropped_items);

        saveChangesInBackend();

        function saveChangesInBackend() {
            var dropped_promise;

            switch (true) {
                case droppedToBacklog(target_list_element):
                    BacklogService.addOrReorderBacklogItemsInBacklog(dropped_items, compared_to);

                    dropped_promise = DroppedService.reorderBacklog(
                        dropped_item_ids,
                        compared_to,
                        BacklogService.backlog
                    );
                    break;

                case droppedToMilestone(source_list_element, target_list_element):
                    var target_milestone_id = getMilestoneId(target_list_element);

                    BacklogService.removeBacklogItemsFromBacklog(dropped_items);
                    MilestoneCollectionService.addOrReorderBacklogItemsInMilestoneContent(target_milestone_id, dropped_items, compared_to);

                    dropped_promise = DroppedService.moveFromBacklogToSubmilestone(
                        dropped_item_ids,
                        compared_to,
                        target_milestone_id
                    ).then(function() {
                        MilestoneCollectionService.refreshMilestone(target_milestone_id);
                    });
                    break;
            }

            dropped_promise.then(function() {
                BacklogItemSelectedService.deselectAllBacklogItems();
            }).catch(function() {
                BacklogItemSelectedService.reselectBacklogItems();
            });
        }
    }

    function droppedToMilestone(source_list_element, target_list_element) {
        return isBacklog(source_list_element) && isAMilestone(target_list_element);
    }

    function droppedToBacklog(target_list_element) {
        return isBacklog(target_list_element);
    }

    function isAMilestone(element) {
        return element.hasClass('submilestone');
    }

    function isBacklog(element) {
        return element.hasClass('backlog');
    }

    function getMilestoneId(milestone_element) {
        return milestone_element.data('submilestone-id');
    }

    function getDroppedItemId(dropped_item) {
        return angular
            .element(dropped_item)
            .data('item-id');
    }

    function isItemDroppable(element_to_drop, target_container_element) {
        var target_container = angular.element(target_container_element);

        if (target_container.data('nodrop')) {
            return false;
        }

        var accepted = target_container.data('accept').split('|'),
            type     = angular.element(element_to_drop).data('type');

        return _(accepted).contains(type);
    }

    function isItemDraggable(element_to_drag, container, handle_element) {
        return ! preventDrag(element_to_drag) &&
            ancestorHasHandleClass(handle_element);
    }

    function ancestorHasHandleClass(handle_element) {
        return angular.element(handle_element)
            .closest('.dragular-handle').length > 0;
    }

    function preventDrag(element_to_drag) {
        return angular.element(element_to_drag).data('nodrag');
    }
}
