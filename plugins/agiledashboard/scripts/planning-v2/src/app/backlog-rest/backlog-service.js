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

import { augment } from "../backlog-item-rest/backlog-item-factory";

export default BacklogService;

BacklogService.$inject = ["$filter", "ProjectService", "BacklogItemCollectionService"];

function BacklogService($filter, ProjectService, BacklogItemCollectionService) {
    const self = this;
    Object.assign(self, {
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
            original_project: undefined,
        },
        items: {
            content: [],
            filtered_content: [],
            loading: false,
            fully_loaded: false,
            pagination: { limit: 50, offset: 0 },
        },
        addOrReorderBacklogItemsInBacklog,
        appendBacklogItems,
        canUserMoveCards,
        filterItems,
        loadMilestoneBacklog,
        loadProjectBacklog,
        removeBacklogItemsFromBacklog,
    });

    function appendBacklogItems(items) {
        const augmented_items = items.map((item) => augment(item));
        self.items.content.push(...augmented_items);
        self.items.loading = false;
    }

    function removeBacklogItemsFromBacklog(items) {
        BacklogItemCollectionService.removeBacklogItemsFromCollection(self.items.content, items);
        BacklogItemCollectionService.removeBacklogItemsFromCollection(
            self.items.filtered_content,
            items,
        );
    }

    function addOrReorderBacklogItemsInBacklog(items, compared_to) {
        BacklogItemCollectionService.addOrReorderBacklogItemsInCollection(
            self.items.content,
            items,
            compared_to,
        );
        BacklogItemCollectionService.addOrReorderBacklogItemsInCollection(
            self.items.filtered_content,
            items,
            compared_to,
        );
    }

    function filterItems(filter_terms) {
        var filtered_items = $filter("InPropertiesFilter")(self.items.content, filter_terms);

        emptyArray(self.items.filtered_content);
        (filtered_items || []).forEach(function (item) {
            self.items.filtered_content.push(item);
        });
    }

    function emptyArray(array) {
        array.length = 0;
    }

    function loadProjectBacklog(project_id) {
        Object.assign(self.backlog, {
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
        Object.assign(self.backlog, {
            rest_base_route: "milestones",
            rest_route_id: milestone.id,
            current_milestone: milestone,
            submilestone_type: milestone.sub_milestone_type,
            accepted_types: milestone.backlog_accepted_types,
            user_can_move_cards: milestone.has_user_priority_change_permission,
            original_project: milestone.original_project_provider,
        });
    }

    function canUserMoveCards() {
        return self.backlog.user_can_move_cards;
    }
}
