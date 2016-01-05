angular
    .module('backlog')
    .controller('BacklogController', BacklogController);

BacklogController.$inject = [
    '$scope',
    'BacklogService',
    'DroppedService',
    'MilestoneCollectionService'
];

function BacklogController(
    $scope,
    BacklogService,
    DroppedService,
    MilestoneCollectionService
) {
    var self = this;
    _.extend(self, {
        details: BacklogService.backlog,
        items  : BacklogService.items,
        displayUserCantPrioritize: displayUserCantPrioritize,
        dragularOptions          : dragularOptions,
        init                     : init,
        isBacklogLoadedAndEmpty  : isBacklogLoadedAndEmpty
    });

    self.init();

    function init() {
        $scope.$on('dragulardrop', dragularDrop);
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
            dropped_item_id     = getDroppedItemId(dropped_item_element);

        if (! target_model) {
            target_model = source_model;
        }

        var compared_to = DroppedService.defineComparedTo(target_model, target_index);

        saveChangesInBackend();

        function saveChangesInBackend() {
            switch (true) {
                case droppedToBacklog(target_list_element):
                    DroppedService.reorderBacklog(
                        dropped_item_id,
                        compared_to,
                        BacklogService.backlog
                    );
                    break;
                case droppedToMilestone(source_list_element, target_list_element):
                    var target_milestone_id = getMilestoneId(target_list_element);

                    DroppedService.moveFromBacklogToSubmilestone(
                        dropped_item_id,
                        compared_to,
                        target_milestone_id
                    ).then(function() {
                        BacklogService.removeItemFromUnfilteredBacklog(dropped_item_id);
                        MilestoneCollectionService.refreshMilestone(target_milestone_id);
                    });
                    break;
            }
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

    function dragularOptions() {
        return {
            containersModel: BacklogService.items.filtered_content,
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
