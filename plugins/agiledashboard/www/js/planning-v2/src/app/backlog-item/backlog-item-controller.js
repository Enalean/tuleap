angular
    .module('backlog-item')
    .controller('BacklogItemController', BacklogItemController);

BacklogItemController.$inject = [
    '$scope',
    '$element',
    'BacklogItemService',
    'dragularService',
    'DroppedService',
    'CardFieldsService',
    'BacklogItemCollectionService',
    'NewTuleapArtifactModalService'
];

function BacklogItemController(
    $scope,
    $element,
    BacklogItemService,
    dragularService,
    DroppedService,
    CardFieldsService,
    BacklogItemCollectionService,
    NewTuleapArtifactModalService
) {
    var self = this;
    _.extend(self, {
        BACKLOG_ITEM_CHILDREN_PAGINATION: { limit: 50, offset: 0 },
        canBeAddedToBacklogItemChildren: canBeAddedToBacklogItemChildren,
        dragularOptions                : dragularOptions,
        init                           : init,
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

    self.init();

    function init() {
        $element.on('dragularenter', dragularEnter);
        $element.on('dragularleave', dragularLeave);
        $element.on('dragularrelease', dragularLeave);
        $scope.$on('dragularcancel', dragularCancel);
        $scope.$on('dragulardrop', dragularDrop);
    }

    function toggleChildrenDisplayed(backlog_item) {
        if (! backlog_item.has_children) {
            return;
        }

        if (! backlog_item.children.loaded) {
            backlog_item.loading = true;
            emptyArray(backlog_item.children.data);
            fetchBacklogItemChildren(backlog_item, self.BACKLOG_ITEM_CHILDREN_PAGINATION.limit, self.BACKLOG_ITEM_CHILDREN_PAGINATION.offset);
        }

        backlog_item.children.collapsed = ! backlog_item.children.collapsed;
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

    function emptyArray(array) {
        array.length = 0;
    }

    function dragularEnter(event) {
        var dropped_item_element = dragularService.shared.item,
            source_list_element  = angular.element(dragularService.shared.source),
            target_element       = event.currentTarget,
            target_list_element  = getListElement(target_element);

        if (! canDropIntoElement(source_list_element, target_list_element, dropped_item_element)) {
            return;
        }

        $element.addClass('appending-child');
    }

    function dragularLeave() {
        $element.removeClass('appending-child');
    }

    function dragularCancel(event, dropped_item_element, source_element) {
        var target_element      = angular.element(dragularService.shared.extra),
            source_list_element = angular.element(source_element);

        if (_.isBoolean(target_element[0])) {
            // dragular sets extra to true if we just drop the element at its original place
            return;
        }

        var backlog_item_element = target_element.closest('backlog-item'),
            target_list_element  = getListElement(backlog_item_element);

        if (! canDropIntoElement(source_list_element, target_list_element, dropped_item_element)) {
            return;
        }

        var dropped_item_id        = getDroppedItemId(dropped_item_element),
            source_backlog_item    = $scope.backlog_item,
            target_backlog_item    = target_list_element.scope().backlog_item,
            target_backlog_item_id = getBacklogItemId(target_list_element),
            initial_index          = dragularService.shared.initialIndex,
            target_index           = 0;

        // the dropped element must be removed for ngRepeat to apply correctly.
        // see dragular's source code at https://github.com/luckylooke/dragular
        source_element.removeChild(dropped_item_element);

        var child_item = pluckDroppedItemFromSourceBacklogItem(source_backlog_item, initial_index);
        target_backlog_item.children.data.unshift(child_item);
        target_backlog_item.has_children = true;

        var compared_to = DroppedService.defineComparedTo(target_backlog_item.children.data, target_index);

        dropChildToAnotherParentItem(
            source_backlog_item.id,
            target_backlog_item_id,
            dropped_item_id,
            compared_to
        );
    }

    function getListElement(element) {
        return angular.element(element)
            .find('.backlog-item-children')
            .last();
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
            source_backlog_item.has_children       = false;
            source_backlog_item.children.collapsed = true;
        }

        return child_item[0];
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
                case droppedToSameParentItem(source_list_element, target_list_element):
                    var current_backlog_item_id = getBacklogItemId(target_list_element);

                    DroppedService.reorderBacklogItemChildren(
                        dropped_item_id,
                        compared_to,
                        current_backlog_item_id
                    );
                    break;
                case droppedToAnotherParentItem(source_list_element, target_list_element):
                    var source_backlog_item_id = getBacklogItemId(source_list_element),
                        target_backlog_item_id = getBacklogItemId(target_list_element);

                    dropChildToAnotherParentItem(
                        source_backlog_item_id,
                        target_backlog_item_id,
                        dropped_item_id,
                        compared_to
                    );
                    break;
            }
        }
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
        dropped_item_id,
        compared_to
    ) {
        BacklogItemCollectionService.items[source_backlog_item_id].updating = true;
        BacklogItemCollectionService.items[target_backlog_item_id].updating   = true;

        var promise = DroppedService.moveFromChildrenToChildren(
            dropped_item_id,
            compared_to,
            source_backlog_item_id,
            target_backlog_item_id
        ).then(function() {
            BacklogItemCollectionService.refreshBacklogItem(source_backlog_item_id);
            BacklogItemCollectionService.refreshBacklogItem(target_backlog_item_id);
        });

        return promise;
    }

    function getBacklogItemId(backlog_item_element) {
        return backlog_item_element.data('backlog-item-id');
    }

    function getDroppedItemId(dropped_item) {
        return angular
            .element(dropped_item)
            .data('item-id');
    }

    function isABacklogItem(element) {
        return element.hasClass('backlog-item-children');
    }

    function dragularOptions(items) {
        return {
            containersModel: items,
            scope          : $scope,
            revertOnSpill  : true,
            nameSpace      : 'dragular-list-children',
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
            .closest('.dragular-handle-child').length > 0;
    }

    function preventDrag(element_to_drag) {
        return angular.element(element_to_drag).data('nodrag');
    }
}
