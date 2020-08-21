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

export default KanbanItemRestService;

KanbanItemRestService.$inject = [
    "$q",
    "Restangular",
    "SharedPropertiesService",
    "RestErrorService",
];

function KanbanItemRestService($q, Restangular, SharedPropertiesService, RestErrorService) {
    Object.assign(Restangular.configuration.defaultHeaders, {
        "X-Client-UUID": SharedPropertiesService.getUUID(),
    });

    return {
        createItem,
        createItemInBacklog,
        getItem,
    };

    function createItem(kanban_id, column_id, label) {
        return Restangular.one("kanban_items").post("", {
            label: label,
            kanban_id: kanban_id,
            column_id: column_id,
        });
    }

    function createItemInBacklog(kanban_id, label) {
        return Restangular.one("kanban_items").post("", {
            label: label,
            kanban_id: kanban_id,
        });
    }

    function getItem(item_id) {
        return Restangular.one("kanban_items", item_id)
            .get()
            .then(function (response) {
                return response.data;
            })
            .catch(catchRestError);
    }

    function catchRestError(data) {
        RestErrorService.reload(data);

        return $q.reject();
    }
}
