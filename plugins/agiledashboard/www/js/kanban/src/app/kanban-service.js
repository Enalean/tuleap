import _ from 'lodash';

export default KanbanService;

KanbanService.$inject = [
    'Restangular',
    '$q',
    '$window',
    'gettextCatalog',
    'SharedPropertiesService',
    'RestErrorService'
];

function KanbanService(
    Restangular,
    $q,
    $window,
    gettextCatalog,
    SharedPropertiesService,
    RestErrorService
) {
    _.extend(Restangular.configuration.defaultHeaders, {
        'X-Client-UUID': SharedPropertiesService.getUUID()
    });

    return {
        getKanban           : getKanban,
        getBacklog          : getBacklog,
        getArchiveSize      : getArchiveSize,
        getBacklogSize      : getBacklogSize,
        getColumnContentSize: getColumnContentSize,
        getArchive          : getArchive,
        getItems            : getItems,
        collapseColumn      : collapseColumn,
        expandColumn        : expandColumn,
        reorderColumn       : reorderColumn,
        reorderBacklog      : reorderBacklog,
        reorderArchive      : reorderArchive,
        expandBacklog       : expandBacklog,
        collapseBacklog     : collapseBacklog,
        expandArchive       : expandArchive,
        collapseArchive     : collapseArchive,
        moveInBacklog       : moveInBacklog,
        moveInArchive       : moveInArchive,
        moveInColumn        : moveInColumn,
        updateKanbanLabel   : updateKanbanLabel,
        deleteKanban        : deleteKanban,
        addColumn           : addColumn,
        reorderColumns      : reorderColumns,
        removeColumn        : removeColumn,
        editColumn          : editColumn,
        updateKanbanName    : updateKanbanName,
        removeKanban        : removeKanban

    };

    function getKanban(id) {
        var data = $q.defer();

        Restangular
            .one('kanban', id)
            .get()
            .then(function (response) {
                data.resolve(response.data);
            });

        return data.promise;
    }

    function getBacklog(id, limit, offset) {
        var data = $q.defer();

        Restangular
            .one('kanban', id)
            .one('backlog')
            .get({
                limit: limit,
                offset: offset
            })
            .then(function (response) {
                var result = {
                    results : augmentItems(response.data.collection),
                    total   : response.headers('X-PAGINATION-SIZE')
                };

                data.resolve(result);
            });

        return data.promise;
    }

    function getBacklogSize(kanban_id) {
        return Restangular
            .one('kanban', kanban_id)
            .one('backlog')
            .head()
            .then(function(response) {
                var headers = response.headers();
                return parseInt(headers['x-pagination-size'], 10);
            });
    }

    function getArchive(id, limit, offset) {
        var data = $q.defer();

        Restangular
            .one('kanban', id)
            .one('archive')
            .get({
                limit: limit,
                offset: offset
            })
            .then(function (response) {
                var result = {
                    results: augmentItems(response.data.collection),
                    total: response.headers('X-PAGINATION-SIZE')
                };

                data.resolve(result);
            });

        return data.promise;
    }

    function getArchiveSize(kanban_id) {
        return Restangular
            .one('kanban', kanban_id)
            .one('archive')
            .head()
            .then(function(response) {
                var headers = response.headers();
                return parseInt(headers['x-pagination-size'], 10);
            });
    }

    function getItems(id, column_id, limit, offset) {
        var data = $q.defer();

        Restangular
            .one('kanban', id)
            .one('items')
            .get({
                column_id: column_id,
                limit: limit,
                offset: offset
            })
            .then(function (response) {
                var result = {
                    results: augmentItems(response.data.collection),
                    total: response.headers('X-PAGINATION-SIZE')
                };

                data.resolve(result);
            });

        return data.promise;
    }

    function getColumnContentSize(kanban_id, column_id) {
        return Restangular
            .one('kanban', kanban_id)
            .one('items')
            .head({
                column_id: column_id
            })
            .then(function(response) {
                var headers = response.headers();
                return parseInt(headers['x-pagination-size'], 10);
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
        return Restangular.one('kanban', kanban_id)
            .all('items')
            .patch({
                order: getOrderArgumentsFromComparedTo(dropped_item_id, compared_to)
            }, {
                column_id: column_id
            })
            .catch(catchRestError);
    }

    function reorderBacklog(kanban_id, dropped_item_id, compared_to) {
        return Restangular.one('kanban', kanban_id)
            .all('backlog')
            .patch({
                order: getOrderArgumentsFromComparedTo(dropped_item_id, compared_to)
            })
            .catch(catchRestError);
    }

    function reorderArchive(kanban_id, dropped_item_id, compared_to) {
        return Restangular.one('kanban', kanban_id)
            .all('archive')
            .patch({
                order: getOrderArgumentsFromComparedTo(dropped_item_id, compared_to)
            })
            .catch(catchRestError);
    }

    function moveInBacklog(kanban_id, dropped_item_id, compared_to, from_column) {
        var patch_arguments = {
            add: {
                ids: [dropped_item_id]
            },
            from_column: from_column
        };
        if (compared_to) {
            patch_arguments.order = getOrderArgumentsFromComparedTo(dropped_item_id, compared_to);
        }

        return Restangular.one('kanban', kanban_id)
            .all('backlog')
            .patch(patch_arguments)
            .catch(catchRestError);
    }

    function moveInArchive(kanban_id, dropped_item_id, compared_to, from_column) {
        var patch_arguments = {
            add: {
                ids: [dropped_item_id]
            },
            from_column: from_column
        };
        if (compared_to) {
            patch_arguments.order = getOrderArgumentsFromComparedTo(dropped_item_id, compared_to);
        }

        return Restangular.one('kanban', kanban_id)
            .all('archive')
            .patch(patch_arguments)
            .catch(catchRestError);
    }

    function moveInColumn(kanban_id, column_id, dropped_item_id, compared_to, from_column) {
        var patch_arguments = {
            add: {
                ids: [dropped_item_id]
            },
            from_column: from_column
        };
        if (compared_to) {
            patch_arguments.order = getOrderArgumentsFromComparedTo(dropped_item_id, compared_to);
        }

        return Restangular.one('kanban', kanban_id)
            .all('items')
            .patch(
                patch_arguments,
                { column_id: column_id }
            )
            .catch(catchRestError);
    }

    function getOrderArgumentsFromComparedTo(dropped_item_id, compared_to) {
        return {
            ids        : [dropped_item_id],
            direction  : compared_to.direction,
            compared_to: compared_to.item_id
        };
    }

    function updateKanbanLabel(kanban_id, kanban_label) {
        return Restangular.one('kanban', kanban_id).patch({
            label: kanban_label
        });
    }

    function deleteKanban(kanban_id) {
        return Restangular.one('kanban', kanban_id).remove();
    }

    function expandColumn(kanban_id, column_id) {
        return Restangular.one('kanban', kanban_id).patch({
            collapse_column: {
                column_id: column_id,
                value: false
            }
        });
    }

    function collapseColumn(kanban_id, column_id) {
        return Restangular.one('kanban', kanban_id).patch({
            collapse_column: {
                column_id: column_id,
                value: true
            }
        });
    }

    function expandBacklog(kanban_id) {
        return Restangular.one('kanban', kanban_id).patch({
            collapse_backlog: false
        });
    }

    function collapseBacklog(kanban_id) {
        return Restangular.one('kanban', kanban_id).patch({
            collapse_backlog: true
        });
    }

    function expandArchive(kanban_id) {
        return Restangular.one('kanban', kanban_id).patch({
            collapse_archive: false
        });
    }

    function collapseArchive(kanban_id) {
        return Restangular.one('kanban', kanban_id).patch({
            collapse_archive: true
        });
    }

    function addColumn(kanban_id, column_label) {
        return Restangular
            .one('kanban', kanban_id)
            .post('columns', {
                label: column_label
            });
    }

    function reorderColumns(kanban_id, sorted_columns_ids) {
        return Restangular
            .one('kanban', kanban_id)
            .all('columns')
            .customPUT(sorted_columns_ids);
    }

    function removeColumn(kanban_id, column_id) {
        return Restangular
            .one('kanban_columns', column_id)
            .remove({
                kanban_id: kanban_id
            });
    }

    function editColumn(kanban_id, column) {
        return Restangular
            .one('kanban_columns', column.id)
            .patch({
                label    : column.label,
                wip_limit: column.limit_input || 0
            }, {
                kanban_id: kanban_id
            });
    }

    function catchRestError(data) {
        RestErrorService.reload(data);

        return $q.reject();
    }

    function updateKanbanName(label) {
        SharedPropertiesService.getKanban().label = label;
    }

    function removeKanban() {
        var message = gettextCatalog.getString(
            'Kanban {{ label }} successfuly deleted',
            { label: SharedPropertiesService.getKanban().label }
        );
        $window.sessionStorage.setItem('tuleap_feedback', message);
        $window.location.href = '/plugins/agiledashboard/?group_id=' + SharedPropertiesService.getProjectId();
    }
}
