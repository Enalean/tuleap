import _ from "lodash";
import angular from "angular";

export default PlanningController;

PlanningController.$inject = [
    "$filter",
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
];

function PlanningController(
    $filter,
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
    ItemAnimatorService
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
        showEditModal: EditItemService.showEditModal,
        $onInit: init,
        canShowBacklogItem,
        displayClosedMilestones,
        displayUserCantPrioritizeForMilestones,
        generateMilestoneLinkUrl,
        getClosedMilestones,
        getOpenMilestones,
        init,
        isMilestoneContext,
        loadInitialMilestones,
        refreshSubmilestone,
        showAddItemToSubMilestoneModal,
        showAddSubmilestoneModal,
        showEditSubmilestoneModal,
        switchClosedMilestoneItemsViewMode,
        switchViewMode,
        thereAreClosedMilestonesLoaded,
        thereAreOpenMilestonesLoaded,
    });

    function init() {
        self.user_id = SharedPropertiesService.getUserId();
        self.project_id = SharedPropertiesService.getProjectId();
        self.milestone_id = parseInt(SharedPropertiesService.getMilestoneId(), 10);

        initViewModes(SharedPropertiesService.getViewMode());
        self.loadInitialMilestones(SharedPropertiesService.getInitialMilestones());
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

    function loadInitialMilestones(initial_milestones) {
        if (initial_milestones) {
            initial_milestones.milestones_representations.forEach((milestone) =>
                MilestoneService.augmentMilestone(milestone, self.items)
            );

            self.milestones.content = initial_milestones.milestones_representations;
            MilestoneCollectionService.milestones.open_milestones_pagination.offset =
                MilestoneCollectionService.milestones.open_milestones_pagination.limit;

            if (
                MilestoneCollectionService.milestones.open_milestones_pagination.offset <
                initial_milestones.total_size
            ) {
                displayOpenMilestones();
            } else {
                self.milestones.loading = false;
            }
        } else {
            displayOpenMilestones();
        }
    }

    function displayOpenMilestones() {
        if (!self.isMilestoneContext()) {
            fetchOpenMilestones(
                self.project_id,
                MilestoneCollectionService.milestones.open_milestones_pagination.limit,
                MilestoneCollectionService.milestones.open_milestones_pagination.offset
            );
        } else {
            fetchOpenSubMilestones(
                self.milestone_id,
                MilestoneCollectionService.milestones.open_milestones_pagination.limit,
                MilestoneCollectionService.milestones.open_milestones_pagination.offset
            );
        }
    }

    function displayClosedMilestones() {
        self.milestones.loading = true;

        if (!self.isMilestoneContext()) {
            fetchClosedMilestones(
                self.project_id,
                MilestoneCollectionService.milestones.closed_milestones_pagination.limit,
                MilestoneCollectionService.milestones.closed_milestones_pagination.offset
            );
        } else {
            fetchClosedSubMilestones(
                self.milestone_id,
                MilestoneCollectionService.milestones.closed_milestones_pagination.limit,
                MilestoneCollectionService.milestones.closed_milestones_pagination.offset
            );
        }
    }

    function fetchOpenMilestones(project_id, limit, offset) {
        self.milestones.loading = true;

        return MilestoneService.getOpenMilestones(project_id, limit, offset, self.items).then(
            function (data) {
                var milestones = [].concat(self.milestones.content).concat(data.results);
                self.milestones.content = _.sortBy(milestones, "id").reverse();

                if (offset + limit < data.total) {
                    fetchOpenMilestones(project_id, limit, offset + limit);
                } else {
                    self.milestones.loading = false;
                    self.milestones.open_milestones_fully_loaded = true;
                }
            }
        );
    }

    function fetchOpenSubMilestones(milestone_id, limit, offset) {
        self.milestones.loading = true;

        return MilestoneService.getOpenSubMilestones(milestone_id, limit, offset, self.items).then(
            function (data) {
                var milestones = [].concat(self.milestones.content).concat(data.results);
                self.milestones.content = _.sortBy(milestones, "id").reverse();

                if (offset + limit < data.total) {
                    fetchOpenSubMilestones(milestone_id, limit, offset + limit);
                } else {
                    self.milestones.loading = false;
                    self.milestones.open_milestones_fully_loaded = true;
                }
            }
        );
    }

    function fetchClosedMilestones(project_id, limit, offset) {
        self.milestones.loading = true;

        return MilestoneService.getClosedMilestones(project_id, limit, offset, self.items).then(
            function (data) {
                var milestones = [].concat(self.milestones.content).concat(data.results);
                self.milestones.content = _.sortBy(milestones, "id").reverse();

                if (offset + limit < data.total) {
                    fetchClosedMilestones(project_id, limit, offset + limit);
                } else {
                    self.milestones.loading = false;
                    self.milestones.closed_milestones_fully_loaded = true;
                }
            }
        );
    }

    function fetchClosedSubMilestones(milestone_id, limit, offset) {
        self.milestones.loading = true;

        return MilestoneService.getClosedSubMilestones(
            milestone_id,
            limit,
            offset,
            self.items
        ).then(function (data) {
            var milestones = [].concat(self.milestones.content).concat(data.results);
            self.milestones.content = _.sortBy(milestones, "id").reverse();

            if (offset + limit < data.total) {
                fetchClosedSubMilestones(milestone_id, limit, offset + limit);
            } else {
                self.milestones.loading = false;
                self.milestones.closed_milestones_fully_loaded = true;
            }
        });
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
            self.refreshSubmilestone
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

        NewTuleapArtifactModalService.showCreation(
            submilestone_type.id,
            self.milestone_id,
            callback
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
            var milestone = data.results;

            if (
                milestone.semantic_status === "closed" &&
                !self.thereAreClosedMilestonesLoaded() &&
                !self.milestones.loading &&
                !self.milestones.closed_milestones_fully_loaded
            ) {
                self.displayClosedMilestones();
            } else {
                self.milestones.content.unshift(data.results);
            }
        });
    }

    function showAddItemToSubMilestoneModal(item_type, parent_item) {
        let compared_to;
        if (!_.isEmpty(parent_item.content)) {
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

        NewTuleapArtifactModalService.showCreation(item_type.id, null, callback);
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
        if (angular.isFunction(backlog_item.isOpen)) {
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
}
