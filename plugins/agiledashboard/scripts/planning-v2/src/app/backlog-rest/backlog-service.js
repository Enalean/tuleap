import _ from "lodash";

export default BacklogService;

BacklogService.$inject = [
    "$filter",
    "BacklogItemFactory",
    "ProjectService",
    "BacklogItemCollectionService",
];

function BacklogService($filter, BacklogItemFactory, ProjectService, BacklogItemCollectionService) {
    var self = this;
    _.extend(self, {
        backlog: {
            accepted_types: {
                toString: function () {
                    return "";
                },
            },
            current_milestone: undefined,
            submilestone_type: undefined,
            rest_base_route: undefined,
            rest_route_id: undefined,
            user_can_move_cards: false,
        },
        items: {
            content: [],
            filtered_content: [],
            loading: false,
            fully_loaded: false,
            pagination: { limit: 50, offset: 0 },
        },
        addOrReorderBacklogItemsInBacklog: addOrReorderBacklogItemsInBacklog,
        appendBacklogItems: appendBacklogItems,
        canUserMoveCards: canUserMoveCards,
        filterItems: filterItems,
        loadMilestoneBacklog: loadMilestoneBacklog,
        loadProjectBacklog: loadProjectBacklog,
        removeBacklogItemsFromBacklog: removeBacklogItemsFromBacklog,
    });

    function appendBacklogItems(items) {
        _(items).forEach(function (item) {
            BacklogItemFactory.augment(item);
            self.items.content.push(item);
        });
        self.items.loading = false;
    }

    function removeBacklogItemsFromBacklog(items) {
        BacklogItemCollectionService.removeBacklogItemsFromCollection(self.items.content, items);
        BacklogItemCollectionService.removeBacklogItemsFromCollection(
            self.items.filtered_content,
            items
        );
    }

    function addOrReorderBacklogItemsInBacklog(items, compared_to) {
        BacklogItemCollectionService.addOrReorderBacklogItemsInCollection(
            self.items.content,
            items,
            compared_to
        );
        BacklogItemCollectionService.addOrReorderBacklogItemsInCollection(
            self.items.filtered_content,
            items,
            compared_to
        );
    }

    function filterItems(filter_terms) {
        var filtered_items = $filter("InPropertiesFilter")(self.items.content, filter_terms);

        emptyArray(self.items.filtered_content);
        _(filtered_items).forEach(function (item) {
            self.items.filtered_content.push(item);
        });
    }

    function emptyArray(array) {
        array.length = 0;
    }

    function loadProjectBacklog(project_id) {
        _.extend(self.backlog, {
            rest_base_route: "projects",
            rest_route_id: project_id,
        });

        fetchProjectBacklogAcceptedTypes(project_id);
        fetchProjectSubmilestoneType(project_id);
    }

    function fetchProjectSubmilestoneType(project_id) {
        return ProjectService.getProject(project_id).then(function (response) {
            self.backlog.submilestone_type =
                response.data.additional_informations.agiledashboard.root_planning.milestone_tracker;
        });
    }

    function fetchProjectBacklogAcceptedTypes(project_id) {
        return ProjectService.getProjectBacklog(project_id).then(function (data) {
            self.backlog.accepted_types = data.allowed_backlog_item_types;
            self.backlog.user_can_move_cards = data.has_user_priority_change_permission;
        });
    }

    function loadMilestoneBacklog(milestone) {
        _.extend(self.backlog, {
            rest_base_route: "milestones",
            rest_route_id: milestone.id,
            current_milestone: milestone,
            submilestone_type: milestone.sub_milestone_type,
            accepted_types: milestone.backlog_accepted_types,
            user_can_move_cards: milestone.has_user_priority_change_permission,
        });
    }

    function canUserMoveCards() {
        return self.backlog.user_can_move_cards;
    }
}
