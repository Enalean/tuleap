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

export default ProjectService;

const headers = { "content-type": "application/json" };

ProjectService.$inject = ["$q"];

function ProjectService($q) {
    return {
        reorderBacklog,
        getProject,
        getProjectBacklog,
        removeAddReorderToBacklog,
        removeAddToBacklog,
    };

    function reorderBacklog(project_id, dropped_item_ids, compared_to) {
        return $q.when(
            patch(encodeURI(`/api/v1/projects/${project_id}/backlog`), {
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

    function removeAddReorderToBacklog(milestone_id, project_id, dropped_item_ids, compared_to) {
        return $q.when(
            patch(encodeURI(`/api/v1/projects/${project_id}/backlog`), {
                headers,
                body: JSON.stringify({
                    order: {
                        ids: dropped_item_ids,
                        direction: compared_to.direction,
                        compared_to: compared_to.item_id,
                    },
                    add: dropped_item_ids.map((dropped_item_id) => ({
                        id: dropped_item_id,
                        remove_from: milestone_id,
                    })),
                }),
            }),
        );
    }

    function removeAddToBacklog(milestone_id, project_id, dropped_item_ids) {
        return $q.when(
            patch(encodeURI(`/api/v1/projects/${project_id}/backlog`), {
                headers,
                body: JSON.stringify({
                    add: dropped_item_ids.map((dropped_item_id) => ({
                        id: dropped_item_id,
                        remove_from: milestone_id,
                    })),
                }),
            }),
        );
    }

    function getProject(project_id) {
        return $q.when(
            get(encodeURI(`/api/v1/projects/${project_id}`))
                .then((response) => response.json())
                .then((project) => {
                    return { data: project };
                }),
        );
    }

    function getProjectBacklog(project_id) {
        /*
         * Use a string for the limit so that the server doesn't recognise a false value
         * and replace it with the default value. This means the response time is much faster.
         */
        const limit = "00";

        return $q.when(
            get(encodeURI(`/api/v2/projects/${project_id}/backlog`), {
                params: { limit, offset: 0 },
            })
                .then((response) => response.json())
                .then((backlog) => {
                    return {
                        allowed_backlog_item_types: getAllowedBacklogItemTypes(backlog),
                        has_user_priority_change_permission:
                            backlog.has_user_priority_change_permission,
                    };
                }),
        );
    }

    function getAllowedBacklogItemTypes(data) {
        const allowed_trackers = data.accept.trackers;
        const parent_trackers = data.accept.parent_trackers;

        return {
            content: allowed_trackers,
            parent_trackers,
            toString() {
                return this.content
                    .map((allowed_tracker) => "trackerId" + allowed_tracker.id)
                    .join("|");
            },
        };
    }
}
