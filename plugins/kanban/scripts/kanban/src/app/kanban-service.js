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

import { get, head, patch, del, post, put } from "@tuleap/tlp-fetch";
import { escaper } from "@tuleap/html-escaper";

export default KanbanService;

KanbanService.$inject = [
    "$q",
    "$window",
    "gettextCatalog",
    "SharedPropertiesService",
    "RestErrorService",
    "FilterTrackerReportService",
];

function KanbanService(
    $q,
    $window,
    gettextCatalog,
    SharedPropertiesService,
    RestErrorService,
    FilterTrackerReportService,
) {
    const headers = {
        "content-type": "application/json",
        "X-Client-UUID": SharedPropertiesService.getUUID(),
    };

    return {
        getArchive,
        getBacklog,
        getItems,
        getArchiveSize,
        getBacklogSize,
        getColumnContentSize,
        collapseColumn,
        expandColumn,
        reorderColumn,
        reorderBacklog,
        reorderArchive,
        expandBacklog,
        collapseBacklog,
        expandArchive,
        collapseArchive,
        moveInBacklog,
        moveInArchive,
        moveInColumn,
        updateKanbanLabel,
        deleteKanban,
        addColumn,
        reorderColumns,
        removeColumn,
        editColumn,
        updateKanbanName,
        removeKanban,
        updateSelectableReports,
    };

    function getAnyColumnItems(url, params) {
        return $q.when(
            get(encodeURI(url), { params }).then((response) => {
                const total = response.headers.get("X-PAGINATION-SIZE");
                return response.json().then((items) => {
                    return { results: augmentItems(items.collection), total };
                });
            }),
        );
    }

    function getAnyColumnSize(url, params) {
        return $q.when(
            head(encodeURI(url), { params }).then((response) => {
                return Number.parseInt(response.headers.get("X-PAGINATION-SIZE"), 10);
            }),
        );
    }

    function getBacklog(kanban_id, limit, offset) {
        let query_params = { limit, offset };
        augmentQueryParamsWithFilterTrackerReport(query_params);
        return getAnyColumnItems(`/api/v1/kanban/${kanban_id}/backlog`, query_params);
    }

    function getArchive(kanban_id, limit, offset) {
        let query_params = { limit, offset };
        augmentQueryParamsWithFilterTrackerReport(query_params);
        return getAnyColumnItems(`/api/v1/kanban/${kanban_id}/archive`, query_params);
    }

    function getItems(kanban_id, column_id, limit, offset) {
        let query_params = { column_id, limit, offset };
        augmentQueryParamsWithFilterTrackerReport(query_params);
        return getAnyColumnItems(`/api/v1/kanban/${kanban_id}/items`, query_params);
    }

    function getBacklogSize(kanban_id) {
        let query_params = {};
        augmentQueryParamsWithFilterTrackerReport(query_params);
        return getAnyColumnSize(`/api/v1/kanban/${kanban_id}/backlog`, query_params);
    }

    function getArchiveSize(kanban_id) {
        let query_params = {};
        augmentQueryParamsWithFilterTrackerReport(query_params);
        return getAnyColumnSize(`/api/v1/kanban/${kanban_id}/archive`, query_params);
    }

    function getColumnContentSize(kanban_id, column_id) {
        let query_params = { column_id };
        augmentQueryParamsWithFilterTrackerReport(query_params);
        return getAnyColumnSize(`/api/v1/kanban/${kanban_id}/items`, query_params);
    }

    function augmentItems(collection) {
        var is_collapsed = SharedPropertiesService.doesUserPrefersCompactCards();
        collection.forEach(function (item) {
            item.is_collapsed = is_collapsed;
        });

        return collection;
    }

    function reorderAnyColumn(url, dropped_item_id, compared_to) {
        return $q.when(
            patch(encodeURI(url), {
                headers,
                body: JSON.stringify({
                    order: getOrderArgumentsFromComparedTo(dropped_item_id, compared_to),
                }),
            }).catch(catchRestError),
        );
    }

    function reorderColumn(kanban_id, column_id, dropped_item_id, compared_to) {
        return reorderAnyColumn(
            `/api/v1/kanban/${kanban_id}/items?column_id=${column_id}`,
            dropped_item_id,
            compared_to,
        );
    }

    function reorderBacklog(kanban_id, dropped_item_id, compared_to) {
        return reorderAnyColumn(
            `/api/v1/kanban/${kanban_id}/backlog`,
            dropped_item_id,
            compared_to,
        );
    }

    function reorderArchive(kanban_id, dropped_item_id, compared_to) {
        return reorderAnyColumn(
            `/api/v1/kanban/${kanban_id}/archive`,
            dropped_item_id,
            compared_to,
        );
    }

    function moveInAnyColumn(url, dropped_item_id, compared_to, from_column) {
        const patch_arguments = {
            add: {
                ids: [dropped_item_id],
            },
            from_column: from_column,
        };
        if (compared_to) {
            patch_arguments.order = getOrderArgumentsFromComparedTo(dropped_item_id, compared_to);
        }

        return $q.when(
            patch(encodeURI(url), {
                headers,
                body: JSON.stringify(patch_arguments),
            }).catch(catchRestError),
        );
    }

    function moveInBacklog(kanban_id, dropped_item_id, compared_to, from_column) {
        return moveInAnyColumn(
            `/api/v1/kanban/${kanban_id}/backlog`,
            dropped_item_id,
            compared_to,
            from_column,
        );
    }

    function moveInArchive(kanban_id, dropped_item_id, compared_to, from_column) {
        return moveInAnyColumn(
            `/api/v1/kanban/${kanban_id}/archive`,
            dropped_item_id,
            compared_to,
            from_column,
        );
    }

    function moveInColumn(kanban_id, column_id, dropped_item_id, compared_to, from_column) {
        return moveInAnyColumn(
            `/api/v1/kanban/${kanban_id}/items?column_id=${column_id}`,
            dropped_item_id,
            compared_to,
            from_column,
        );
    }

    function getOrderArgumentsFromComparedTo(dropped_item_id, compared_to) {
        return {
            ids: [dropped_item_id],
            direction: compared_to.direction,
            compared_to: compared_to.item_id,
        };
    }

    function updateKanbanLabel(kanban_id, kanban_label) {
        return $q.when(
            patch(encodeURI(`/api/v1/kanban/${kanban_id}`), {
                headers,
                body: JSON.stringify({ label: kanban_label }),
            }),
        );
    }

    function deleteKanban(kanban_id) {
        return $q.when(del(encodeURI(`/api/v1/kanban/${kanban_id}`), { headers }));
    }

    function expandColumn(kanban_id, column_id) {
        return $q.when(
            patch(encodeURI(`/api/v1/kanban/${kanban_id}`), {
                headers,
                body: JSON.stringify({ collapse_column: { column_id, value: false } }),
            }),
        );
    }

    function collapseColumn(kanban_id, column_id) {
        return $q.when(
            patch(encodeURI(`/api/v1/kanban/${kanban_id}`), {
                headers,
                body: JSON.stringify({ collapse_column: { column_id, value: true } }),
            }),
        );
    }

    function expandBacklog(kanban_id) {
        return $q.when(
            patch(encodeURI(`/api/v1/kanban/${kanban_id}`), {
                headers,
                body: JSON.stringify({ collapse_backlog: false }),
            }),
        );
    }

    function collapseBacklog(kanban_id) {
        return $q.when(
            patch(encodeURI(`/api/v1/kanban/${kanban_id}`), {
                headers,
                body: JSON.stringify({ collapse_backlog: true }),
            }),
        );
    }

    function expandArchive(kanban_id) {
        return $q.when(
            patch(encodeURI(`/api/v1/kanban/${kanban_id}`), {
                headers,
                body: JSON.stringify({ collapse_archive: false }),
            }),
        );
    }

    function collapseArchive(kanban_id) {
        return $q.when(
            patch(encodeURI(`/api/v1/kanban/${kanban_id}`), {
                headers,
                body: JSON.stringify({ collapse_archive: true }),
            }),
        );
    }

    function addColumn(kanban_id, column_label) {
        return $q.when(
            post(encodeURI(`/api/v1/kanban/${kanban_id}/columns`), {
                headers,
                body: JSON.stringify({ label: column_label }),
            }).then((response) => response.json()),
        );
    }

    function reorderColumns(kanban_id, sorted_columns_ids) {
        return $q.when(
            put(encodeURI(`/api/v1/kanban/${kanban_id}/columns`), {
                headers,
                body: JSON.stringify(sorted_columns_ids),
            }),
        );
    }

    function removeColumn(kanban_id, column_id) {
        return $q.when(
            del(encodeURI(`/api/v1/kanban_columns/${column_id}?kanban_id=${kanban_id}`), {
                headers,
            }),
        );
    }

    function editColumn(kanban_id, column) {
        return $q.when(
            patch(encodeURI(`/api/v1/kanban_columns/${column.id}?kanban_id=${kanban_id}`), {
                headers,
                body: JSON.stringify({ label: column.label, wip_limit: column.limit_input || 0 }),
            }),
        );
    }

    function catchRestError(error) {
        RestErrorService.reload(error);

        return $q.reject();
    }

    function updateKanbanName(label) {
        SharedPropertiesService.getKanban().label = label;
    }

    function removeKanban() {
        const message = gettextCatalog.getString("Kanban {{ label }} successfully deleted", {
            label: escaper.html(SharedPropertiesService.getKanban().label),
        });
        $window.sessionStorage.setItem("tuleap_feedback", message);
        $window.location.href = SharedPropertiesService.getKanbanHomepageUrl();
    }

    function augmentQueryParamsWithFilterTrackerReport(query_params) {
        const selected_filter_tracker_report_id =
            FilterTrackerReportService.getSelectedFilterTrackerReportId();

        if (selected_filter_tracker_report_id) {
            query_params.query = JSON.stringify({
                tracker_report_id: selected_filter_tracker_report_id,
            });
        }
    }

    function updateSelectableReports(kanban_id, selectable_report_ids) {
        return $q.when(
            put(encodeURI(`/api/v1/kanban/${kanban_id}/tracker_reports`), {
                headers,
                body: JSON.stringify({ tracker_report_ids: selectable_report_ids }),
            }),
        );
    }
}
