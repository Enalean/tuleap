angular
    .module('milestone')
    .controller('MilestoneController', MilestoneController);


MilestoneController.$inject = [
    '$scope',
    'BacklogService',
    'DroppedService',
    'MilestoneCollectionService'
];

function MilestoneController(
    $scope,
    BacklogService,
    DroppedService,
    MilestoneCollectionService
) {
    var self = this;
    _.extend(self, {
        dragularOptions          : dragularOptions,
        init                     : init,
        isMilestoneLoadedAndEmpty: isMilestoneLoadedAndEmpty,
        toggleMilestone          : toggleMilestone
    });

    self.init();

    function init() {
        $scope.$on('dragulardrop', dragularDrop);
    }

    function toggleMilestone($event, milestone) {
        if (! milestone.alreadyLoaded && milestone.content.length === 0) {
            milestone.getContent();
        }

        var target                = $event.target;
        var is_a_create_item_link = false;

        if (target.classList) {
            is_a_create_item_link = target.classList.contains('create-item-link');
        } else {
            is_a_create_item_link = target.parentNode.getElementsByClassName("create-item-link")[0] !== undefined;
        }

        if (! is_a_create_item_link) {
            return milestone.collapsed = ! milestone.collapsed;
        }
    }

    function isMilestoneLoadedAndEmpty(milestone) {
        return ! milestone.loadingContent &&
            milestone.content.length === 0;
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

        var source_list_element  = angular.element(source_element),
            target_list_element  = angular.element(target_element),
            dropped_item_id      = getDroppedItemId(dropped_item_element),
            current_milestone_id = getMilestoneId(target_list_element);

        if (! target_model) {
            target_model = source_model;
        }

        var compared_to = DroppedService.defineComparedTo(target_model, target_index);

        saveChangesInBackend();

        function saveChangesInBackend() {
            var source_milestone_id = getMilestoneId(source_list_element);

            switch (true) {
                case droppedToSameMilestone(source_list_element, target_list_element):
                    DroppedService.reorderSubmilestone(
                        dropped_item_id,
                        compared_to,
                        current_milestone_id
                    );
                    break;
                case droppedToAnotherMilestone(source_list_element, target_list_element):
                    var target_milestone_id = getMilestoneId(target_list_element);

                    DroppedService.moveFromSubmilestoneToSubmilestone(
                        dropped_item_id,
                        compared_to,
                        source_milestone_id,
                        target_milestone_id
                    ).then(function() {
                        MilestoneCollectionService.refreshMilestone(source_milestone_id);
                        MilestoneCollectionService.refreshMilestone(target_milestone_id);
                    });
                    break;
                case droppedToBacklog(target_list_element):
                    DroppedService.moveFromSubmilestoneToBacklog(
                        dropped_item_id,
                        compared_to,
                        source_milestone_id,
                        BacklogService.backlog
                    ).then(function() {
                        var dropped_backlog_item = target_model[target_index];
                        BacklogService.insertItemInUnfilteredBacklog(dropped_backlog_item, target_index);
                        MilestoneCollectionService.refreshMilestone(source_milestone_id);
                    });
                    break;
            }
        }
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

    function dragularOptions(items) {
        return {
            containersModel: items,
            scope          : $scope,
            revertOnSpill  : true,
            nameSpace      : 'dragular-list',
            accepts        : isItemDroppable,
            moves          : isItemDraggable
        };
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
