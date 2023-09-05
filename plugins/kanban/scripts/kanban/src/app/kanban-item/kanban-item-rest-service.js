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

import { get, post } from "@tuleap/tlp-fetch";

export default KanbanItemRestService;

KanbanItemRestService.$inject = ["$q", "SharedPropertiesService", "RestErrorService"];

function KanbanItemRestService($q, SharedPropertiesService, RestErrorService) {
    const headers = {
        "content-type": "application/json",
        "X-Client-UUID": SharedPropertiesService.getUUID(),
    };

    return {
        createItem,
        createItemInBacklog,
        getItem,
    };

    function createItem(kanban_id, column_id, label) {
        return $q.when(
            post(encodeURI("/api/v1/kanban_items"), {
                headers,
                body: JSON.stringify({ label, kanban_id, column_id }),
            }).then((response) => response.json()),
        );
    }

    function createItemInBacklog(kanban_id, label) {
        return $q.when(
            post(encodeURI("/api/v1/kanban_items"), {
                headers,
                body: JSON.stringify({ label, kanban_id }),
            }).then((response) => response.json()),
        );
    }

    function getItem(item_id) {
        return $q.when(
            get(encodeURI(`/api/v1/kanban_items/${item_id}`)).then(
                (response) => response.json(),
                catchRestError,
            ),
        );
    }

    function catchRestError(error) {
        RestErrorService.reload(error);

        return $q.reject();
    }
}
