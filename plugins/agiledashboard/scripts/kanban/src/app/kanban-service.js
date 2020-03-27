export default KanbanService;

KanbanService.$inject = [
    "Restangular",
    "$http",
    "$q",
    "$window",
    "gettextCatalog",
    "SharedPropertiesService",
    "RestErrorService",
    "FilterTrackerReportService",
];

function KanbanService(
    Restangular,
    $http,
    $q,
    $window,
    gettextCatalog,
    SharedPropertiesService,
    RestErrorService,
    FilterTrackerReportService
) {
    Object.assign(Restangular.configuration.defaultHeaders, {
        "X-Client-UUID": SharedPropertiesService.getUUID(),
    });

    return {
        getKanban,
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

    function getKanban(id) {
        var data = $q.defer();

        Restangular.one("kanban", id)
            .get()
            .then(function (response) {
                data.resolve(response.data);
            });

        return data.promise;
    }

    function getBacklog(id, limit, offset) {
        const data = $q.defer();
        let query_params = {
            limit: limit,
            offset: offset,
        };

        augmentQueryParamsWithFilterTrackerReport(query_params);

        Restangular.one("kanban", id)
            .one("backlog")
            .get(query_params)
            .then(function (response) {
                var result = {
                    results: augmentItems(response.data.collection),
                    total: response.headers("X-PAGINATION-SIZE"),
                };

                data.resolve(result);
            });

        return data.promise;
    }

    function getBacklogSize(kanban_id) {
        let query_params = {};
        augmentQueryParamsWithFilterTrackerReport(query_params);

        return Restangular.one("kanban", kanban_id)
            .one("backlog")
            .head(query_params)
            .then((response) => {
                var headers = response.headers();
                return parseInt(headers["x-pagination-size"], 10);
            });
    }

    function getArchive(id, limit, offset) {
        const data = $q.defer();
        let query_params = {
            limit: limit,
            offset: offset,
        };

        augmentQueryParamsWithFilterTrackerReport(query_params);

        Restangular.one("kanban", id)
            .one("archive")
            .get(query_params)
            .then(function (response) {
                var result = {
                    results: augmentItems(response.data.collection),
                    total: response.headers("X-PAGINATION-SIZE"),
                };

                data.resolve(result);
            });

        return data.promise;
    }

    function getArchiveSize(kanban_id) {
        let query_params = {};
        augmentQueryParamsWithFilterTrackerReport(query_params);

        return Restangular.one("kanban", kanban_id)
            .one("archive")
            .head(query_params)
            .then((response) => {
                var headers = response.headers();
                return parseInt(headers["x-pagination-size"], 10);
            });
    }

    function getItems(id, column_id, limit, offset) {
        const data = $q.defer();
        let query_params = {
            column_id: column_id,
            limit: limit,
            offset: offset,
        };

        augmentQueryParamsWithFilterTrackerReport(query_params);

        Restangular.one("kanban", id)
            .one("items")
            .get(query_params)
            .then(function (response) {
                var result = {
                    results: augmentItems(response.data.collection),
                    total: response.headers("X-PAGINATION-SIZE"),
                };

                data.resolve(result);
            });

        return data.promise;
    }

    function getColumnContentSize(kanban_id, column_id) {
        let query_params = {
            column_id,
        };
        augmentQueryParamsWithFilterTrackerReport(query_params);

        return Restangular.one("kanban", kanban_id)
            .one("items")
            .head(query_params)
            .then((response) => {
                var headers = response.headers();
                return parseInt(headers["x-pagination-size"], 10);
            });
    }

    function augmentItems(collection) {
        var is_collapsed = SharedPropertiesService.doesUserPrefersCompactCards();
        collection.forEach(function (item) {
            item.is_collapsed = is_collapsed;
        });

        return collection;
    }

    function reorderColumn(kanban_id, column_id, dropped_item_id, compared_to) {
        return Restangular.one("kanban", kanban_id)
            .all("items")
            .patch(
                {
                    order: getOrderArgumentsFromComparedTo(dropped_item_id, compared_to),
                },
                {
                    column_id: column_id,
                }
            )
            .catch(catchRestError);
    }

    function reorderBacklog(kanban_id, dropped_item_id, compared_to) {
        return Restangular.one("kanban", kanban_id)
            .all("backlog")
            .patch({
                order: getOrderArgumentsFromComparedTo(dropped_item_id, compared_to),
            })
            .catch(catchRestError);
    }

    function reorderArchive(kanban_id, dropped_item_id, compared_to) {
        return Restangular.one("kanban", kanban_id)
            .all("archive")
            .patch({
                order: getOrderArgumentsFromComparedTo(dropped_item_id, compared_to),
            })
            .catch(catchRestError);
    }

    function moveInBacklog(kanban_id, dropped_item_id, compared_to, from_column) {
        var patch_arguments = {
            add: {
                ids: [dropped_item_id],
            },
            from_column: from_column,
        };
        if (compared_to) {
            patch_arguments.order = getOrderArgumentsFromComparedTo(dropped_item_id, compared_to);
        }

        return Restangular.one("kanban", kanban_id)
            .all("backlog")
            .patch(patch_arguments)
            .catch(catchRestError);
    }

    function moveInArchive(kanban_id, dropped_item_id, compared_to, from_column) {
        var patch_arguments = {
            add: {
                ids: [dropped_item_id],
            },
            from_column: from_column,
        };
        if (compared_to) {
            patch_arguments.order = getOrderArgumentsFromComparedTo(dropped_item_id, compared_to);
        }

        return Restangular.one("kanban", kanban_id)
            .all("archive")
            .patch(patch_arguments)
            .catch(catchRestError);
    }

    function moveInColumn(kanban_id, column_id, dropped_item_id, compared_to, from_column) {
        var patch_arguments = {
            add: {
                ids: [dropped_item_id],
            },
            from_column: from_column,
        };
        if (compared_to) {
            patch_arguments.order = getOrderArgumentsFromComparedTo(dropped_item_id, compared_to);
        }

        return Restangular.one("kanban", kanban_id)
            .all("items")
            .patch(patch_arguments, { column_id: column_id })
            .catch(catchRestError);
    }

    function getOrderArgumentsFromComparedTo(dropped_item_id, compared_to) {
        return {
            ids: [dropped_item_id],
            direction: compared_to.direction,
            compared_to: compared_to.item_id,
        };
    }

    function updateKanbanLabel(kanban_id, kanban_label) {
        return Restangular.one("kanban", kanban_id).patch({
            label: kanban_label,
        });
    }

    function deleteKanban(kanban_id) {
        return Restangular.one("kanban", kanban_id).remove();
    }

    function expandColumn(kanban_id, column_id) {
        return Restangular.one("kanban", kanban_id).patch({
            collapse_column: {
                column_id: column_id,
                value: false,
            },
        });
    }

    function collapseColumn(kanban_id, column_id) {
        return Restangular.one("kanban", kanban_id).patch({
            collapse_column: {
                column_id: column_id,
                value: true,
            },
        });
    }

    function expandBacklog(kanban_id) {
        return Restangular.one("kanban", kanban_id).patch({
            collapse_backlog: false,
        });
    }

    function collapseBacklog(kanban_id) {
        return Restangular.one("kanban", kanban_id).patch({
            collapse_backlog: true,
        });
    }

    function expandArchive(kanban_id) {
        return Restangular.one("kanban", kanban_id).patch({
            collapse_archive: false,
        });
    }

    function collapseArchive(kanban_id) {
        return Restangular.one("kanban", kanban_id).patch({
            collapse_archive: true,
        });
    }

    function addColumn(kanban_id, column_label) {
        return Restangular.one("kanban", kanban_id).post("columns", {
            label: column_label,
        });
    }

    function reorderColumns(kanban_id, sorted_columns_ids) {
        return Restangular.one("kanban", kanban_id).all("columns").customPUT(sorted_columns_ids);
    }

    function removeColumn(kanban_id, column_id) {
        return Restangular.one("kanban_columns", column_id).remove({
            kanban_id: kanban_id,
        });
    }

    function editColumn(kanban_id, column) {
        return Restangular.one("kanban_columns", column.id).patch(
            {
                label: column.label,
                wip_limit: column.limit_input || 0,
            },
            {
                kanban_id: kanban_id,
            }
        );
    }

    function catchRestError(data) {
        RestErrorService.reload(data);

        return $q.reject();
    }

    function updateKanbanName(label) {
        SharedPropertiesService.getKanban().label = label;
    }

    function removeKanban() {
        var message = gettextCatalog.getString("Kanban {{ label }} successfuly deleted", {
            label: SharedPropertiesService.getKanban().label,
        });
        $window.sessionStorage.setItem("tuleap_feedback", message);
        $window.location.href =
            "/plugins/agiledashboard/?group_id=" + SharedPropertiesService.getProjectId();
    }

    function augmentQueryParamsWithFilterTrackerReport(query_params) {
        const selected_filter_tracker_report_id = FilterTrackerReportService.getSelectedFilterTrackerReportId();

        if (selected_filter_tracker_report_id) {
            query_params.query = { tracker_report_id: selected_filter_tracker_report_id };
        }
    }

    function updateSelectableReports(kanban_id, selectable_report_ids) {
        return $http.put("/api/v1/kanban/" + kanban_id + "/tracker_reports", {
            tracker_report_ids: selectable_report_ids,
        });
    }
}
