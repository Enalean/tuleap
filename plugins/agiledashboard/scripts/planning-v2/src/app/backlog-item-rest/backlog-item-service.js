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
import { augment } from "./backlog-item-factory";

export default BacklogItemService;

const headers = { "content-type": "application/json" };

BacklogItemService.$inject = ["$q"];

function BacklogItemService($q) {
    const self = this;
    Object.assign(self, {
        getBacklogItem,
        getProjectBacklogItems,
        getMilestoneBacklogItems,
        getBacklogItemChildren,
        reorderBacklogItemChildren,
        removeAddReorderBacklogItemChildren,
        removeAddBacklogItemChildren,
        removeItemFromExplicitBacklog,
    });

    function getBacklogItem(backlog_item_id) {
        return $q.when(
            get(encodeURI(`/api/v1/backlog_items/${backlog_item_id}`))
                .then((response) => response.json())
                .then((backlog_item) => {
                    return { backlog_item: augment(backlog_item) };
                }),
        );
    }

    function getProjectBacklogItems(project_id, limit, offset) {
        return $q.when(
            get(encodeURI(`/api/v1/projects/${project_id}/backlog`), {
                params: { limit, offset },
            }).then((response) => {
                const total = response.headers.get("X-PAGINATION-SIZE");
                return response.json().then((backlog_items) => {
                    const augmented = backlog_items.map(augment);
                    return { results: augmented, total };
                });
            }),
        );
    }

    function getMilestoneBacklogItems(milestone_id, limit, offset) {
        return $q.when(
            get(encodeURI(`/api/v1/milestones/${milestone_id}/backlog`), {
                params: { limit, offset },
            }).then((response) => {
                const total = response.headers.get("X-PAGINATION-SIZE");
                return response.json().then((backlog_items) => {
                    const augmented = backlog_items.map(augment);
                    return { results: augmented, total };
                });
            }),
        );
    }

    function getBacklogItemChildren(backlog_item_id, limit, offset) {
        return $q.when(
            get(encodeURI(`/api/v1/backlog_items/${backlog_item_id}/children`), {
                params: { limit, offset },
            }).then((response) => {
                const total = response.headers.get("X-PAGINATION-SIZE");
                return response.json().then((backlog_items) => {
                    const augmented = backlog_items.map(augment);
                    return { results: augmented, total };
                });
            }),
        );
    }

    function reorderBacklogItemChildren(backlog_item_id, dropped_item_ids, compared_to) {
        return $q.when(
            patch(encodeURI(`/api/v1/backlog_items/${backlog_item_id}/children`), {
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

    function removeAddReorderBacklogItemChildren(
        source_backlog_item_id,
        dest_backlog_item_id,
        dropped_item_ids,
        compared_to,
    ) {
        return $q.when(
            patch(encodeURI(`/api/v1/backlog_items/${dest_backlog_item_id}/children`), {
                headers,
                body: JSON.stringify({
                    order: {
                        ids: dropped_item_ids,
                        direction: compared_to.direction,
                        compared_to: compared_to.item_id,
                    },
                    add: dropped_item_ids.map((dropped_item_id) => ({
                        id: dropped_item_id,
                        remove_from: source_backlog_item_id,
                    })),
                }),
            }),
        );
    }

    function removeAddBacklogItemChildren(
        source_backlog_item_id,
        dest_backlog_item_id,
        dropped_item_ids,
    ) {
        return $q.when(
            patch(encodeURI(`/api/v1/backlog_items/${dest_backlog_item_id}/children`), {
                headers,
                body: JSON.stringify({
                    add: dropped_item_ids.map((dropped_item_id) => ({
                        id: dropped_item_id,
                        remove_from: source_backlog_item_id,
                    })),
                }),
            }),
        );
    }

    function removeItemFromExplicitBacklog(project_id, backlog_items) {
        return $q.when(
            patch(encodeURI(`/api/v1/projects/${project_id}/backlog`), {
                headers,
                body: JSON.stringify({
                    remove: backlog_items.map((backlog_item) => ({
                        id: backlog_item.id,
                    })),
                }),
            }),
        );
    }
}
