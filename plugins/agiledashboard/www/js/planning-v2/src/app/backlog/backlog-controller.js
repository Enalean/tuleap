angular
    .module('backlog')
    .controller('BacklogController', BacklogController);

BacklogController.$inject = [
    '$q',
    '$scope',
    '$document',
    'dragularService',
    'BacklogService',
    'MilestoneService',
    'BacklogItemService',
    'BacklogItemCollectionService',
    'ProjectService',
    'DroppedService',
    'MilestoneCollectionService',
    'BacklogItemSelectedService',
    'SharedPropertiesService',
    'NewTuleapArtifactModalService',
    'BacklogFilterValue'
];

function BacklogController(
    $q,
    $scope,
    $document,
    dragularService,
    BacklogService,
    MilestoneService,
    BacklogItemService,
    BacklogItemCollectionService,
    ProjectService,
    DroppedService,
    MilestoneCollectionService,
    BacklogItemSelectedService,
    SharedPropertiesService,
    NewTuleapArtifactModalService,
    BacklogFilterValue
) {
    var self = this;
    _.extend(self, {
        project_id                   : SharedPropertiesService.getProjectId(),
        milestone_id                 : parseInt(SharedPropertiesService.getMilestoneId(), 10),
        details                      : BacklogService.backlog,
        backlog_items                : BacklogService.items,
        all_backlog_items            : BacklogItemCollectionService.items,
        filter                       : BacklogFilterValue,
        dragular_instance_for_backlog: undefined,
        canUserMoveCards             : BacklogService.canUserMoveCards,
        displayBacklogItems          : displayBacklogItems,
        displayUserCantPrioritize    : displayUserCantPrioritize,
        dragularOptionsForBacklog    : dragularOptionsForBacklog,
        fetchAllBacklogItems         : fetchAllBacklogItems,
        fetchBacklogItems            : fetchBacklogItems,
        filterBacklog                : filterBacklog,
        init                         : init,
        isBacklogLoadedAndEmpty      : isBacklogLoadedAndEmpty,
        isMilestoneContext           : isMilestoneContext,
        loadBacklog                  : loadBacklog,
        loadInitialBacklogItems      : loadInitialBacklogItems,
        moveToBottom                 : moveToBottom,
        moveToTop                    : moveToTop,
        reorderBacklogItems          : reorderBacklogItems,
        showAddBacklogItemModal      : showAddBacklogItemModal
    });

    init();
    initDragular();

    function init() {
        self.loadBacklog(SharedPropertiesService.getMilestone());
        self.loadInitialBacklogItems(SharedPropertiesService.getInitialBacklogItems());
    }

    function initDragular() {
        var backlog_element = angular.element('ul.backlog');

        self.dragular_instance_for_backlog = dragularService(backlog_element, self.dragularOptionsForBacklog());

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

    function isMilestoneContext() {
        return ! isNaN(self.milestone_id);
    }

    function loadBacklog(initial_milestone) {
        if (! self.isMilestoneContext()) {
            BacklogService.loadProjectBacklog(self.project_id);
        } else if (initial_milestone) {
            MilestoneService.defineAllowedBacklogItemTypes(initial_milestone);
            MilestoneService.augmentMilestone(initial_milestone, self.all_backlog_items);

            BacklogService.loadMilestoneBacklog(initial_milestone);
        } else {
            MilestoneService.getMilestone(self.milestone_id, self.all_backlog_items).then(function(data) {
                BacklogService.loadMilestoneBacklog(data.results);
            });
        }
    }

    function loadInitialBacklogItems(initial_backlog_items) {
        if (initial_backlog_items) {
            appendBacklogItems(initial_backlog_items.backlog_items_representations);

            self.backlog_items.pagination.offset = self.backlog_items.pagination.limit;
            self.backlog_items.fully_loaded      = self.backlog_items.pagination.offset >= initial_backlog_items.total_size;
        } else {
            displayBacklogItems();
        }
    }

    function displayBacklogItems() {
        if (backlogItemsAreLoadingOrAllLoaded()) {
            return $q.when();
        }

        return self.fetchBacklogItems(self.backlog_items.pagination.limit, self.backlog_items.pagination.offset).then(function(total) {
            self.backlog_items.pagination.offset += self.backlog_items.pagination.limit;
            self.backlog_items.fully_loaded       = self.backlog_items.pagination.offset >= total;
        });
    }

    function fetchBacklogItems(limit, offset) {
        self.backlog_items.loading = true;
        var promise;

        if (self.isMilestoneContext()) {
            promise = BacklogItemService.getMilestoneBacklogItems(self.milestone_id, limit, offset);
        } else {
            promise = BacklogItemService.getProjectBacklogItems(self.project_id, limit, offset);
        }

        return promise.then(function(data) {
            var items = data.results;
            appendBacklogItems(items);

            return data.total;
        });
    }

    function fetchAllBacklogItems(limit, offset) {
        if (backlogItemsAreLoadingOrAllLoaded()) {
            return $q.reject();
        }

        return self.fetchBacklogItems(limit, offset).then(function(total) {
            if ((offset + limit) > total) {
                self.backlog_items.fully_loaded = true;
                return;
            } else {
                return fetchAllBacklogItems(limit, offset + limit);
            }
        });
    }

    function filterBacklog() {
        self.fetchAllBacklogItems(
            self.backlog_items.pagination.limit,
            self.backlog_items.pagination.offset
        ).finally(function() {
            BacklogService.filterItems(self.filter.terms);
        });
    }

    function appendBacklogItems(items) {
        _.extend(self.all_backlog_items, _.indexBy(items, 'id'));
        BacklogService.appendBacklogItems(items);
        BacklogService.filterItems(self.filter.terms);
    }

    function prependItemToBacklog(backlog_item_id) {
        return prependItemToFilteredBacklog(backlog_item_id).then(function(new_item) {
            self.backlog_items.filtered_content.unshift(new_item);
        });
    }

    function prependItemToFilteredBacklog(backlog_item_id) {
        return BacklogItemService.getBacklogItem(backlog_item_id).then(function(data) {
            var new_item = data.backlog_item;
            self.all_backlog_items[backlog_item_id] = new_item;
            self.backlog_items.content.unshift(new_item);

            return new_item;
        });
    }

    function backlogItemsAreLoadingOrAllLoaded() {
        return (self.backlog_items.loading || self.backlog_items.fully_loaded);
    }

    function showAddBacklogItemModal($event, item_type) {
        $event.preventDefault();

        var compared_to;
        if (! _.isEmpty(self.backlog_items.content)) {
            compared_to = {
                direction: "before",
                item_id  : self.backlog_items.content[0].id
            };
        }

        function callback(item_id) {
            var promise;
            if (! self.isMilestoneContext()) {
                if (compared_to) {
                    promise = ProjectService.removeAddReorderToBacklog(undefined, self.details.rest_route_id, [item_id], compared_to);
                } else {
                    promise = ProjectService.removeAddToBacklog(undefined, self.details.rest_route_id, [item_id]);
                }
            } else if (compared_to) {
                promise = MilestoneService.removeAddReorderToBacklog(undefined, self.details.rest_route_id, [item_id], compared_to);
            } else {
                promise = MilestoneService.removeAddToBacklog(undefined, self.details.rest_route_id, [item_id]);
            }

            promise.then(function() {
                var subpromise;
                if (self.filter.terms) {
                    subpromise = prependItemToFilteredBacklog(item_id);
                } else {
                    subpromise = prependItemToBacklog(item_id);
                }
                return subpromise;
            });

            return promise;
        }

        var parent_item = (! _.isEmpty(self.current_milestone)) ? self.current_milestone : undefined;
        NewTuleapArtifactModalService.showCreation(item_type.id, parent_item, callback);
    }

    function dragularOptionsForBacklog() {
        return {
            containersModel: self.backlog_items.filtered_content,
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
        return ! BacklogService.items.loading
            && BacklogService.items.content.length === 0;
    }

    function moveToTop(backlog_item) {
        var moved_items = [backlog_item],
            compared_to;

        if (BacklogItemSelectedService.areThereMultipleSelectedBaklogItems()) {
            moved_items = BacklogItemSelectedService.getCompactedSelectedBacklogItem();
        }

        compared_to = DroppedService.defineComparedToBeFirstItem(self.backlog_items.content, moved_items);

        self.reorderBacklogItems(moved_items, compared_to);
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

        self.fetchAllBacklogItems(self.backlog_items.pagination.limit, self.backlog_items.pagination.offset).finally(function() {
            compared_to = DroppedService.defineComparedToBeLastItem(self.backlog_items.content, moved_items);

            backlog_item.moving_to = false;

            self.reorderBacklogItems(moved_items, compared_to);
        });
    }

    function dragularDrag(event, element) {
        event.stopPropagation();

        if (BacklogItemSelectedService.areThereMultipleSelectedBaklogItems()
            && BacklogItemSelectedService.isDraggedBacklogItemSelected(getDroppedItemId(element))
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
            switch (true) {
                case droppedToBacklog(target_list_element):
                    self.reorderBacklogItems(dropped_items, compared_to);
                    break;

                case droppedToMilestone(source_list_element, target_list_element):
                    var target_milestone_id = getMilestoneId(target_list_element);

                    BacklogService.removeBacklogItemsFromBacklog(dropped_items);
                    MilestoneCollectionService.addOrReorderBacklogItemsInMilestoneContent(target_milestone_id, dropped_items, compared_to);

                    DroppedService.moveFromBacklogToSubmilestone(
                        dropped_item_ids,
                        compared_to,
                        target_milestone_id
                    ).then(function() {
                        MilestoneCollectionService.refreshMilestone(target_milestone_id);
                        BacklogItemSelectedService.deselectAllBacklogItems();
                    }).catch(function() {
                        BacklogItemSelectedService.reselectBacklogItems();
                    });
                    break;
            }
        }
    }

    function reorderBacklogItems(backlog_items, compared_to) {
        BacklogService.addOrReorderBacklogItemsInBacklog(backlog_items, compared_to);

        return DroppedService.reorderBacklog(
            _.pluck(backlog_items, 'id'),
            compared_to,
            BacklogService.backlog
        ).then(function() {
            BacklogItemSelectedService.deselectAllBacklogItems();
        }).catch(function() {
            BacklogItemSelectedService.reselectBacklogItems();
        });
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
        return (! ancestorCannotBeDragged(handle_element)
            && ancestorHasHandleClass(handle_element));
    }

    function ancestorHasHandleClass(handle_element) {
        return angular.element(handle_element)
            .closest('.dragular-handle').length > 0;
    }

    function ancestorCannotBeDragged(handle_element) {
        return (angular.element(handle_element)
            .closest('[data-nodrag="true"]').length > 0);
    }
}
