/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import "./planner-view.tpl.html";
import { sortByStartDateAscending } from "../milestone/sort-helper";
import { extractErrorMessage } from "@tuleap/plugin-agiledashboard-shared";
import {
    getClosedSubMilestones,
    getClosedTopMilestones,
    getOpenSubMilestones,
    getOpenTopMilestones,
} from "../api/rest-querier";
import { isEmpty } from "lodash-es";
import { isFunction } from "angular";
import { sprintf } from "sprintf-js";
import { SESSION_STORAGE_KEY } from "../session";

export default {
    templateUrl: "planner-view.tpl.html",
    controller,
    controllerAs: "planning",
};

controller.$inject = [
    "$document",
    "$filter",
    "$q",
    "$scope",
    "$window",
    "SharedPropertiesService",
    "BacklogService",
    "BacklogItemService",
    "MilestoneService",
    "NewTuleapArtifactModalService",
    "UserPreferencesService",
    "RestErrorService",
    "BacklogItemCollectionService",
    "MilestoneCollectionService",
    "BacklogItemSelectedService",
    "EditItemService",
    "ItemAnimatorService",
    "ErrorState",
    "gettextCatalog",
];

function controller(
    $document,
    $filter,
    $q,
    $scope,
    $window,
    SharedPropertiesService,
    BacklogService,
    BacklogItemService,
    MilestoneService,
    NewTuleapArtifactModalService,
    UserPreferencesService,
    RestErrorService,
    BacklogItemCollectionService,
    MilestoneCollectionService,
    BacklogItemSelectedService,
    EditItemService,
    ItemAnimatorService,
    ErrorState,
    gettextCatalog
) {
    const self = this;

    Object.assign(self, {
        backlog: BacklogService.backlog,
        items: BacklogItemCollectionService.items,
        milestones: MilestoneCollectionService.milestones,
        compact_view_key: "compact-view",
        detailed_view_key: "detailed-view",
        show_closed_view_key: "show-closed-view",
        hide_closed_view_key: "hide-closed-view",
        loading_modal: NewTuleapArtifactModalService.loading,

        getRestError: RestErrorService.getError,
        getNumberOfSelectedBacklogItem: BacklogItemSelectedService.getNumberOfSelectedBacklogItem,
        $onInit: init,
        canShowBacklogItem,
        displayClosedMilestones,
        displayUserCantPrioritizeForMilestones,
        generateMilestoneLinkUrl,
        getClosedMilestones,
        getOpenMilestones,
        isMilestoneContext,
        loadInitialMilestones,
        loadOpenAndClosedMilestones,
        refreshSubmilestone,
        showAddItemToSubMilestoneModal,
        showAddSubmilestoneModal,
        showEditSubmilestoneModal,
        switchClosedMilestoneItemsViewMode,
        switchViewMode,
        thereAreClosedMilestonesLoaded,
        thereAreOpenMilestonesLoaded,
        canUserCreateMilestone,
        hasOriginalProject,
    });

    function init() {
        self.user_id = SharedPropertiesService.getUserId();
        self.project_id = SharedPropertiesService.getProjectId();
        self.milestone_id = parseInt(SharedPropertiesService.getMilestoneId(), 10);

        initViewModes(SharedPropertiesService.getViewMode());
        if (SharedPropertiesService.shouldloadOpenAndClosedMilestones()) {
            self.loadOpenAndClosedMilestones();
        } else {
            self.loadInitialMilestones();
        }

        $document.find(`[data-shortcut-create-option]`).on("click", ($event) => {
            if (
                parseInt($event.target.dataset.trackerId, 10) === self.backlog.submilestone_type.id
            ) {
                if (self.canUserCreateMilestone()) {
                    showAddSubmilestoneModal($event, self.backlog.submilestone_type);
                }
            }
        });
    }

    function initViewModes(view_mode) {
        self.current_view_class = self.compact_view_key;
        self.current_closed_view_class = self.show_closed_view_key;

        if (view_mode) {
            self.current_view_class = view_mode;
        }
    }

    function switchViewMode(view_mode) {
        self.current_view_class = view_mode;
        UserPreferencesService.setPreference(
            self.user_id,
            "agiledashboard_planning_item_view_mode_" + self.project_id,
            view_mode
        );
    }

    function switchClosedMilestoneItemsViewMode(view_mode) {
        self.current_closed_view_class = view_mode;
    }

    function isMilestoneContext() {
        return !isNaN(self.milestone_id);
    }

    function milestoneProgressCallback(milestones) {
        milestones.forEach((milestone) => {
            MilestoneService.augmentMilestone(milestone, self.items);
        });
        self.milestones.content = self.milestones.content.concat(milestones);
        $scope.$apply();
        self.milestones.content.sort(sortByStartDateAscending);
        return milestones;
    }

    function loadInitialMilestones() {
        const promise = !self.isMilestoneContext()
            ? getOpenTopMilestones(self.project_id, milestoneProgressCallback)
            : getOpenSubMilestones(self.milestone_id, milestoneProgressCallback);

        self.milestones.loading = true;
        $q.when(promise).then(
            () => {
                self.milestones.loading = false;
                self.milestones.open_milestones_fully_loaded = true;
            },
            async (error) => {
                const message = await extractErrorMessage(error);
                ErrorState.setError(message);
            }
        );
    }

    function displayClosedMilestones() {
        const promise = !self.isMilestoneContext()
            ? getClosedTopMilestones(self.project_id, milestoneProgressCallback)
            : getClosedSubMilestones(self.milestone_id, milestoneProgressCallback);

        self.milestones.loading = true;
        $q.when(promise).then(() => {
            self.milestones.loading = false;
            self.milestones.closed_milestones_fully_loaded = true;
        });
    }

    function loadOpenAndClosedMilestones() {
        const open_promise = !self.isMilestoneContext()
            ? getOpenTopMilestones(self.project_id, milestoneProgressCallback)
            : getOpenSubMilestones(self.milestone_id, milestoneProgressCallback);

        const closed_promise = !self.isMilestoneContext()
            ? getClosedTopMilestones(self.project_id, milestoneProgressCallback)
            : getClosedSubMilestones(self.milestone_id, milestoneProgressCallback);

        self.milestones.loading = true;
        $q.all([open_promise, closed_promise]).then(
            () => {
                self.milestones.loading = false;
                self.milestones.open_milestones_fully_loaded = true;
                self.milestones.closed_milestones_fully_loaded = true;
            },
            async (error) => {
                const message = await extractErrorMessage(error);
                ErrorState.setError(message);
            }
        );
    }

    function getOpenMilestones() {
        return $filter("filter")(self.milestones.content, { semantic_status: "open" });
    }

    function getClosedMilestones() {
        return $filter("filter")(self.milestones.content, { semantic_status: "closed" });
    }

    function thereAreOpenMilestonesLoaded() {
        var open_milestones = getOpenMilestones();

        return open_milestones.length > 0;
    }

    function thereAreClosedMilestonesLoaded() {
        var closed_milestones = getClosedMilestones();

        return closed_milestones.length > 0;
    }

    function generateMilestoneLinkUrl(milestone, pane) {
        return (
            "?group_id=" +
            self.project_id +
            "&planning_id=" +
            milestone.planning.id +
            "&action=show&aid=" +
            milestone.id +
            "&pane=" +
            pane
        );
    }

    function showEditSubmilestoneModal($event, submilestone) {
        $event.stopPropagation();
        $event.preventDefault();

        NewTuleapArtifactModalService.showEdition(
            self.user_id,
            submilestone.artifact.tracker.id,
            submilestone.artifact.id,
            (submilestone_id, changes) => {
                if (changes.did_artifact_links_change) {
                    $window.sessionStorage.setItem(
                        SESSION_STORAGE_KEY,
                        sprintf(
                            gettextCatalog.getString("Successfully updated milestone '%s'"),
                            submilestone.label
                        )
                    );
                    $window.location.reload();
                    return;
                }
                self.refreshSubmilestone(submilestone_id);
            }
        );
    }

    function showAddSubmilestoneModal($event, submilestone_type) {
        $event.preventDefault();

        function callback(submilestone_id) {
            if (!self.isMilestoneContext()) {
                return prependSubmilestoneToSubmilestoneList(submilestone_id);
            }

            return addSubmilestoneToSubmilestone(submilestone_id);
        }

        const parent_artifact_id = self.isMilestoneContext() ? self.milestone_id : null;

        NewTuleapArtifactModalService.showCreation(
            self.user_id,
            submilestone_type.id,
            parent_artifact_id,
            callback,
            []
        );
    }

    function addSubmilestoneToSubmilestone(submilestone_id) {
        return MilestoneService.patchSubMilestones(self.backlog.rest_route_id, [
            submilestone_id,
        ]).then(function () {
            return prependSubmilestoneToSubmilestoneList(submilestone_id);
        });
    }

    function prependSubmilestoneToSubmilestoneList(submilestone_id) {
        return MilestoneService.getMilestone(submilestone_id, self.items).then(function (data) {
            const milestone = data.results;

            if (
                milestone.semantic_status === "closed" &&
                !self.thereAreClosedMilestonesLoaded() &&
                !self.milestones.loading &&
                !self.milestones.closed_milestones_fully_loaded
            ) {
                self.displayClosedMilestones();
            } else {
                addMilestone(milestone);
            }
        });
    }

    function addMilestone(milestone) {
        self.milestones.content.unshift(milestone);
        self.milestones.content.sort(sortByStartDateAscending);
    }

    function showAddItemToSubMilestoneModal(item_type, parent_item) {
        let compared_to;
        if (!isEmpty(parent_item.content)) {
            compared_to = {
                direction: "before",
                item_id: parent_item.content[0].id,
            };
        }

        function callback(item_id) {
            let promise;
            if (compared_to) {
                promise = MilestoneService.addReorderToContent(
                    parent_item.id,
                    [item_id],
                    compared_to
                );
            } else {
                promise = MilestoneService.addToContent(parent_item.id, [item_id]);
            }

            return promise.then(() => {
                return prependItemToSubmilestone(item_id, parent_item);
            });
        }

        NewTuleapArtifactModalService.showCreation(self.user_id, item_type.id, null, callback, []);
    }

    function prependItemToSubmilestone(child_item_id, parent_item) {
        return BacklogItemService.getBacklogItem(child_item_id).then(({ backlog_item }) => {
            ItemAnimatorService.animateCreated(backlog_item);
            self.items[child_item_id] = backlog_item;

            parent_item.content.unshift(backlog_item);
            MilestoneService.updateInitialEffort(parent_item);
        });
    }

    function refreshSubmilestone(submilestone_id) {
        var submilestone = self.milestones.content.find(({ id }) => id === submilestone_id);

        submilestone.updating = true;

        return MilestoneService.getMilestone(submilestone_id).then(function (data) {
            submilestone.label = data.results.label;
            submilestone.capacity = data.results.capacity;
            submilestone.semantic_status = data.results.semantic_status;
            submilestone.status_value = data.results.status_value;
            submilestone.start_date = data.results.start_date;
            submilestone.end_date = data.results.end_date;
            submilestone.updating = false;
        });
    }

    function canShowBacklogItem(backlog_item) {
        if (isFunction(backlog_item.isOpen)) {
            return (
                backlog_item.isOpen() ||
                self.current_closed_view_class === self.show_closed_view_key
            );
        }

        return true;
    }

    function hideUserCantPrioritizeForMilestones() {
        if (self.milestones.content.length === 0) {
            return true;
        }

        return self.milestones.content[0].has_user_priority_change_permission;
    }

    function displayUserCantPrioritizeForMilestones() {
        return !hideUserCantPrioritizeForMilestones();
    }

    function canUserCreateMilestone() {
        return self.backlog.submilestone_type && self.backlog.user_can_move_cards;
    }

    function hasOriginalProject() {
        return (
            self.backlog.original_project !== null && self.backlog.original_project !== undefined
        );
    }
}
