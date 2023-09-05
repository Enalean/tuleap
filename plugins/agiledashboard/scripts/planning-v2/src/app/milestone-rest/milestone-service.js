/*
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

import { get, patch } from "@tuleap/tlp-fetch";
import { augment } from "../backlog-item-rest/backlog-item-factory";

export default MilestoneService;

const headers = { "content-type": "application/json" };

MilestoneService.$inject = ["$q"];

function MilestoneService($q) {
    const self = this;
    Object.assign(self, {
        milestone_content_pagination: { limit: 50, offset: 0 },
        getMilestone,
        patchSubMilestones,
        getContent,
        reorderBacklog,
        removeAddReorderToBacklog,
        removeAddToBacklog,
        reorderContent,
        addReorderToContent,
        addToContent,
        removeAddReorderToContent,
        removeAddToContent,
        updateInitialEffort,
        defineAllowedBacklogItemTypes,
        augmentMilestone,
    });

    function getMilestone(milestone_id, scope_items) {
        return $q.when(
            get(encodeURI(`/api/v1/milestones/${milestone_id}`))
                .then((response) => response.json())
                .then((milestone) => {
                    defineAllowedBacklogItemTypes(milestone);
                    augmentMilestone(milestone, scope_items);

                    return { results: milestone };
                }),
        );
    }

    function patchSubMilestones(milestone_id, submilestone_ids) {
        return $q.when(
            patch(encodeURI(`/api/v1/milestones/${milestone_id}/milestones`), {
                headers,
                body: JSON.stringify({
                    add: submilestone_ids.map((id) => ({ id })),
                }),
            }),
        );
    }

    function getContent(milestone_id, limit, offset) {
        return $q.when(
            get(encodeURI(`/api/v1/milestones/${milestone_id}/content`), {
                params: { limit, offset },
            }).then((response) => {
                const total = response.headers.get("X-PAGINATION-SIZE");
                return response.json().then((content_items) => {
                    return { results: content_items, total };
                });
            }),
        );
    }

    function augmentMilestone(milestone, scope_items) {
        addContentDataToMilestone(milestone);
        setMilestoneCollapsedByDefault(milestone);
        defineAllowedContentItemTypes(milestone);

        function setMilestoneCollapsedByDefault(milestone) {
            milestone.collapsed = true;
        }

        function addContentDataToMilestone(milestone) {
            milestone.content = [];
            milestone.initialEffort = 0;
            milestone.getContent = function () {
                milestone.loadingContent = true;
                milestone.alreadyLoaded = true;

                return fetchMilestoneContent(
                    self.milestone_content_pagination.limit,
                    self.milestone_content_pagination.offset,
                );
            };

            function fetchMilestoneContent(limit, offset) {
                return getContent(milestone.id, limit, offset).then((data) => {
                    data.results.forEach((backlog_item) => {
                        const augmented = augment(backlog_item);
                        scope_items[augmented.id] = augmented;

                        milestone.content.push(scope_items[augmented.id]);
                    });

                    updateInitialEffort(milestone);

                    if (limit + offset < data.total) {
                        return fetchMilestoneContent(limit, offset + limit);
                    }
                    milestone.loadingContent = false;
                });
            }
        }
    }

    function updateInitialEffort(milestone) {
        milestone.initialEffort = milestone.content.reduce(
            (previous_sum, backlog_item) => previous_sum + backlog_item.initial_effort,
            0,
        );
    }

    function defineAllowedBacklogItemTypes(milestone) {
        const allowed_trackers = milestone.resources.backlog.accept.trackers;
        const parent_trackers = milestone.resources.backlog.accept.parent_trackers;

        milestone.backlog_accepted_types = {
            content: allowed_trackers,
            parent_trackers,
            toString() {
                return this.content
                    .map((allowed_tracker) => "trackerId" + allowed_tracker.id)
                    .join("|");
            },
        };
    }

    function defineAllowedContentItemTypes(milestone) {
        var allowed_trackers = milestone.resources.content.accept.trackers;

        milestone.content_accepted_types = {
            content: allowed_trackers,
            toString() {
                return this.content
                    .map((allowed_tracker) => "trackerId" + allowed_tracker.id)
                    .join("|");
            },
        };
    }

    function reorderBacklog(milestone_id, dropped_item_ids, compared_to) {
        return $q.when(
            patch(encodeURI(`/api/v1/milestones/${milestone_id}/backlog`), {
                headers,
                body: JSON.stringify({
                    order: {
                        ids: dropped_item_ids,
                        direction: compared_to.direction,
                        compared_to: compared_to.item_id,
                    },
                }),
            }),
        );
    }

    function removeAddReorderToBacklog(
        source_milestone_id,
        dest_milestone_id,
        dropped_item_ids,
        compared_to,
    ) {
        return $q.when(
            patch(encodeURI(`/api/v1/milestones/${dest_milestone_id}/backlog`), {
                headers,
                body: JSON.stringify({
                    order: {
                        ids: dropped_item_ids,
                        direction: compared_to.direction,
                        compared_to: compared_to.item_id,
                    },
                    add: dropped_item_ids.map((dropped_item_id) => ({
                        id: dropped_item_id,
                        remove_from: source_milestone_id,
                    })),
                }),
            }),
        );
    }

    function removeAddToBacklog(source_milestone_id, dest_milestone_id, dropped_item_ids) {
        return $q.when(
            patch(encodeURI(`/api/v1/milestones/${dest_milestone_id}/backlog`), {
                headers,
                body: JSON.stringify({
                    add: dropped_item_ids.map((dropped_item_id) => ({
                        id: dropped_item_id,
                        remove_from: source_milestone_id,
                    })),
                }),
            }),
        );
    }

    function reorderContent(milestone_id, dropped_item_ids, compared_to) {
        return $q.when(
            patch(encodeURI(`/api/v1/milestones/${milestone_id}/content`), {
                headers,
                body: JSON.stringify({
                    order: {
                        ids: dropped_item_ids,
                        direction: compared_to.direction,
                        compared_to: compared_to.item_id,
                    },
                }),
            }),
        );
    }

    function addReorderToContent(milestone_id, dropped_item_ids, compared_to) {
        return $q.when(
            patch(encodeURI(`/api/v1/milestones/${milestone_id}/content`), {
                headers,
                body: JSON.stringify({
                    order: {
                        ids: dropped_item_ids,
                        direction: compared_to.direction,
                        compared_to: compared_to.item_id,
                    },
                    add: dropped_item_ids.map((id) => ({ id })),
                }),
            }),
        );
    }

    function addToContent(milestone_id, dropped_item_ids) {
        return $q.when(
            patch(encodeURI(`/api/v1/milestones/${milestone_id}/content`), {
                headers,
                body: JSON.stringify({
                    add: dropped_item_ids.map((id) => ({ id })),
                }),
            }),
        );
    }

    function removeAddReorderToContent(
        source_milestone_id,
        dest_milestone_id,
        dropped_item_ids,
        compared_to,
    ) {
        return $q.when(
            patch(encodeURI(`/api/v1/milestones/${dest_milestone_id}/content`), {
                headers,
                body: JSON.stringify({
                    order: {
                        ids: dropped_item_ids,
                        direction: compared_to.direction,
                        compared_to: compared_to.item_id,
                    },
                    add: dropped_item_ids.map((dropped_item_id) => ({
                        id: dropped_item_id,
                        remove_from: source_milestone_id,
                    })),
                }),
            }),
        );
    }

    function removeAddToContent(source_milestone_id, dest_milestone_id, dropped_item_ids) {
        return $q.when(
            patch(encodeURI(`/api/v1/milestones/${dest_milestone_id}/content`), {
                headers,
                body: JSON.stringify({
                    add: dropped_item_ids.map((dropped_item_id) => ({
                        id: dropped_item_id,
                        remove_from: source_milestone_id,
                    })),
                }),
            }),
        );
    }
}
