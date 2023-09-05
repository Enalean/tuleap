/*
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import angular from "angular";
import { extend, keyBy, isEmpty } from "lodash-es";
import BacklogFilterValue from "../backlog-filter-terms.js";

export default BacklogController;

BacklogController.$inject = [
    "$q",
    "$scope",
    "$document",
    "dragularService",
    "gettextCatalog",
    "BacklogService",
    "MilestoneService",
    "BacklogItemService",
    "BacklogItemCollectionService",
    "ProjectService",
    "DroppedService",
    "MilestoneCollectionService",
    "BacklogItemSelectedService",
    "SharedPropertiesService",
    "NewTuleapArtifactModalService",
    "ItemAnimatorService",
];

function BacklogController(
    $q,
    $scope,
    $document,
    dragularService,
    gettextCatalog,
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
    ItemAnimatorService,
) {
    const self = this;
    Object.assign(self, {
        project_id: SharedPropertiesService.getProjectId(),
        milestone_id: parseInt(SharedPropertiesService.getMilestoneId(), 10),
        details: BacklogService.backlog,
        backlog_items: BacklogService.items,
        all_backlog_items: BacklogItemCollectionService.items,
        filter: BacklogFilterValue,
        dragular_instance_for_backlog: undefined,
        canUserMoveCards: BacklogService.canUserMoveCards,
        $onInit: init,
        displayBacklogItems,
        displayUserCantPrioritize,
        dragularOptionsForBacklog,
        fetchAllBacklogItems,
        fetchBacklogItems,
        filterBacklog,
        isBacklogLoadedAndEmpty,
        isMilestoneContext,
        loadBacklog,
        loadInitialBacklogItems,
        moveToBottom,
        moveToTop,
        reorderBacklogItems,
        showAddBacklogItemModal,
        soloButtonCanBeDisplayed,
        addItemButtonCanBeDisplayed,
    });

    function init() {
        initDragular();
        self.loadBacklog();
        self.loadInitialBacklogItems();

        $document.find(`[data-shortcut-create-option]`).on("click", ($event) => {
            if (!("content" in self.details.accepted_types)) {
                return;
            }
            if (self.details.accepted_types.content.length === 0) {
                return;
            }

            const clicked_tracker_id = parseInt($event.target.dataset.trackerId, 10);
            const backlog_item_tracker = self.details.accepted_types.content.find(
                (tracker) => tracker.id === clicked_tracker_id,
            );
            if (backlog_item_tracker) {
                showAddBacklogItemModal($event, backlog_item_tracker);
                return;
            }

            if (self.details.accepted_types.parent_trackers.length === 0) {
                return;
            }
            const parent_tracker = self.details.accepted_types.parent_trackers.find(
                (tracker) => tracker.id === clicked_tracker_id,
            );
            if (parent_tracker) {
                showAddBacklogItemModal($event, parent_tracker);
            }
        });
    }

    function initDragular() {
        var backlog_element = angular.element("div.backlog");

        self.dragular_instance_for_backlog = dragularService(
            backlog_element,
            self.dragularOptionsForBacklog(),
        );

        $scope.$on("dragulardrop", dragularDrop);
        $scope.$on("dragularcancel", dragularCancel);
        $scope.$on("dragulardrag", dragularDrag);

        $document.bind("keyup", function (event) {
            var esc_key_code = 27;

            if (event.keyCode === esc_key_code) {
                BacklogItemSelectedService.deselectAllBacklogItems();

                self.dragular_instance_for_backlog.cancel(true);

                $scope.$apply();
            }
        });
    }

    function isMilestoneContext() {
        return !isNaN(self.milestone_id);
    }

    function loadBacklog() {
        if (!self.isMilestoneContext()) {
            BacklogService.loadProjectBacklog(self.project_id);
        } else {
            MilestoneService.getMilestone(self.milestone_id, self.all_backlog_items).then(
                function (data) {
                    BacklogService.loadMilestoneBacklog(data.results);
                },
            );
        }
    }

    function loadInitialBacklogItems() {
        displayBacklogItems();
    }

    function displayBacklogItems() {
        if (backlogItemsAreLoadingOrAllLoaded()) {
            return $q.when();
        }

        return self
            .fetchBacklogItems(
                self.backlog_items.pagination.limit,
                self.backlog_items.pagination.offset,
            )
            .then(function (total) {
                self.backlog_items.pagination.offset += self.backlog_items.pagination.limit;
                self.backlog_items.fully_loaded = self.backlog_items.pagination.offset >= total;
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

        return promise.then(function (data) {
            var items = data.results;
            appendBacklogItems(items);

            return data.total;
        });
    }

    function fetchAllBacklogItems(limit, offset) {
        if (backlogItemsAreLoadingOrAllLoaded()) {
            return $q.reject();
        }

        return self.fetchBacklogItems(limit, offset).then(function (total) {
            if (offset + limit > total) {
                self.backlog_items.fully_loaded = true;
            } else {
                return fetchAllBacklogItems(limit, offset + limit);
            }
        });
    }

    function filterBacklog() {
        self.fetchAllBacklogItems(
            self.backlog_items.pagination.limit,
            self.backlog_items.pagination.offset,
        )
            .catch(() => {
                // ignore rejection
            })
            .finally(function () {
                BacklogService.filterItems(self.filter.terms);
            });
    }

    function appendBacklogItems(items) {
        extend(self.all_backlog_items, keyBy(items, "id"));
        BacklogService.appendBacklogItems(items);
        BacklogService.filterItems(self.filter.terms);
    }

    function prependItemToBacklog(backlog_item_id) {
        return prependItemToFilteredBacklog(backlog_item_id).then((new_item) => {
            self.backlog_items.filtered_content.unshift(new_item);
        });
    }

    function prependItemToFilteredBacklog(backlog_item_id) {
        return BacklogItemService.getBacklogItem(backlog_item_id).then(({ backlog_item }) => {
            ItemAnimatorService.animateCreated(backlog_item);
            self.all_backlog_items[backlog_item_id] = backlog_item;
            self.backlog_items.content.unshift(backlog_item);

            return backlog_item;
        });
    }

    function backlogItemsAreLoadingOrAllLoaded() {
        return self.backlog_items.loading || self.backlog_items.fully_loaded;
    }

    function showAddBacklogItemModal($event, item_type) {
        $event.preventDefault();

        var compared_to;
        if (!isEmpty(self.backlog_items.content)) {
            compared_to = {
                direction: "before",
                item_id: self.backlog_items.content[0].id,
            };
        }

        function callback(item_id) {
            let promise;
            if (!self.isMilestoneContext()) {
                if (compared_to) {
                    promise = ProjectService.removeAddReorderToBacklog(
                        undefined,
                        self.details.rest_route_id,
                        [item_id],
                        compared_to,
                    );
                } else {
                    promise = ProjectService.removeAddToBacklog(
                        undefined,
                        self.details.rest_route_id,
                        [item_id],
                    );
                }
            } else if (compared_to) {
                promise = MilestoneService.removeAddReorderToBacklog(
                    undefined,
                    self.details.rest_route_id,
                    [item_id],
                    compared_to,
                );
            } else {
                promise = MilestoneService.removeAddToBacklog(
                    undefined,
                    self.details.rest_route_id,
                    [item_id],
                );
            }

            promise.then(() => {
                if (self.filter.terms) {
                    return prependItemToFilteredBacklog(item_id);
                }
                return prependItemToBacklog(item_id);
            });

            return promise;
        }

        NewTuleapArtifactModalService.showCreation(
            SharedPropertiesService.getUserId(),
            item_type.id,
            null,
            callback,
            [],
        );
    }

    function dragularOptionsForBacklog() {
        return {
            containersModel: self.backlog_items.filtered_content,
            scope: $scope,
            revertOnSpill: true,
            nameSpace: "dragular-list",
            accepts: isItemDroppable,
            moves: isItemDraggable,
        };
    }

    function hideUserCantPrioritize() {
        return (
            BacklogService.backlog.user_can_move_cards || BacklogService.items.content.length === 0
        );
    }

    function displayUserCantPrioritize() {
        return !hideUserCantPrioritize();
    }

    function isBacklogLoadedAndEmpty() {
        return !BacklogService.items.loading && BacklogService.items.content.length === 0;
    }

    function moveToTop(backlog_item) {
        var moved_items = [backlog_item],
            compared_to;

        if (BacklogItemSelectedService.areThereMultipleSelectedBaklogItems()) {
            moved_items = BacklogItemSelectedService.getCompactedSelectedBacklogItem();
        }

        compared_to = DroppedService.defineComparedToBeFirstItem(
            self.backlog_items.content,
            moved_items,
        );

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

        self.fetchAllBacklogItems(
            self.backlog_items.pagination.limit,
            self.backlog_items.pagination.offset,
        ).finally(function () {
            compared_to = DroppedService.defineComparedToBeLastItem(
                self.backlog_items.content,
                moved_items,
            );

            backlog_item.moving_to = false;

            self.reorderBacklogItems(moved_items, compared_to);
        });
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

        function saveChangesInBackend() {
            switch (true) {
                case droppedToBacklog(target_list_element):
                    self.reorderBacklogItems(dropped_items, compared_to);
                    break;

                case droppedToMilestone(source_list_element, target_list_element):
                    var target_milestone_id = getMilestoneId(target_list_element);

                    BacklogService.removeBacklogItemsFromBacklog(dropped_items);
                    MilestoneCollectionService.addOrReorderBacklogItemsInMilestoneContent(
                        target_milestone_id,
                        dropped_items,
                        compared_to,
                    );

                    DroppedService.moveFromBacklogToSubmilestone(
                        dropped_item_ids,
                        compared_to,
                        target_milestone_id,
                    )
                        .then(function () {
                            MilestoneCollectionService.refreshMilestone(target_milestone_id);
                            BacklogItemSelectedService.deselectAllBacklogItems();
                        })
                        .catch(function () {
                            BacklogItemSelectedService.reselectBacklogItems();
                        });
                    break;
            }
        }
    }

    function reorderBacklogItems(backlog_items, compared_to) {
        BacklogService.addOrReorderBacklogItemsInBacklog(backlog_items, compared_to);

        return DroppedService.reorderBacklog(
            backlog_items.map((backlog_item) => backlog_item.id),
            compared_to,
            BacklogService.backlog,
        )
            .then(function () {
                BacklogItemSelectedService.deselectAllBacklogItems();
            })
            .catch(function () {
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

    function soloButtonCanBeDisplayed() {
        return (
            self.canUserMoveCards() &&
            "content" in self.details.accepted_types &&
            self.details.accepted_types.content.length === 1
        );
    }

    function addItemButtonCanBeDisplayed() {
        return (
            "content" in self.details.accepted_types &&
            self.details.accepted_types.content.length > 1 &&
            self.details.user_can_move_cards
        );
    }
}
