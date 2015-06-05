(function () {
    angular
        .module('kanban')
        .service('KanbanService', KanbanService);

    KanbanService.$inject = ['Restangular', '$q'];

    function KanbanService(Restangular, $q) {
        var rest = Restangular.withConfig(function(RestangularConfigurer) {
            RestangularConfigurer.setFullResponse(true);
            RestangularConfigurer.setBaseUrl('/api/v1');
        });

        return {
            getKanban           : getKanban,
            getBacklog          : getBacklog,
            getArchive          : getArchive,
            getItems            : getItems,
            reorderColumn       : reorderColumn,
            reorderBacklog      : reorderBacklog,
            reorderArchive      : reorderArchive,
            moveInBacklog       : moveInBacklog,
            moveInArchive       : moveInArchive,
            moveInColumn        : moveInColumn,
            setWipLimitForColumn: setWipLimitForColumn,
            updateKanbanLabel   : updateKanbanLabel,
            deleteKanban        : deleteKanban
        };

        function getKanban(id) {
            var data = $q.defer();

            rest
                .one('kanban', id)
                .get()
                .then(function (response) {
                    data.resolve(response.data);
                });

            return data.promise;
        }

        function getBacklog(id, limit, offset) {
            var data = $q.defer();

            rest
                .one('kanban', id)
                .one('backlog')
                .get({
                    limit: limit,
                    offset: offset
                })
                .then(function (response) {
                    var result = {
                        results              : response.data.collection,
                        user_can_add_in_place: response.data.user_can_add_in_place,
                        total                : response.headers('X-PAGINATION-SIZE')
                    };

                    data.resolve(result);
                });

            return data.promise;
        }

        function getArchive(id, limit, offset) {
            var data = $q.defer();

            rest
                .one('kanban', id)
                .one('archive')
                .get({
                    limit: limit,
                    offset: offset
                })
                .then(function (response) {
                    var result = {
                        results: response.data.collection,
                        total: response.headers('X-PAGINATION-SIZE')
                    };

                    data.resolve(result);
                });

            return data.promise;
        }

        function getItems(id, column_id, limit, offset) {
            var data = $q.defer();

            rest
                .one('kanban', id)
                .one('items')
                .get({
                    column_id: column_id,
                    limit: limit,
                    offset: offset
                })
                .then(function (response) {
                    var result = {
                        results: response.data.collection,
                        total: response.headers('X-PAGINATION-SIZE')
                    };

                    data.resolve(result);
                });

            return data.promise;
        }

        function reorderColumn(kanban_id, column_id, dropped_item_id, compared_to) {
            return rest.one('kanban', kanban_id)
                .all('items')
                .patch({
                    order: getOrderArgumentsFromComparedTo(dropped_item_id, compared_to)
                }, {
                    column_id: column_id
                });
        }

        function reorderBacklog(kanban_id, dropped_item_id, compared_to) {
            return rest.one('kanban', kanban_id)
                .all('backlog')
                .patch({
                    order: getOrderArgumentsFromComparedTo(dropped_item_id, compared_to)
                });
        }

        function reorderArchive(kanban_id, dropped_item_id, compared_to) {
            return rest.one('kanban', kanban_id)
                .all('archive')
                .patch({
                    order: getOrderArgumentsFromComparedTo(dropped_item_id, compared_to)
                });
        }

        function moveInBacklog(kanban_id, dropped_item_id, compared_to) {
            var patch_arguments = {
                add: {
                    ids : [dropped_item_id]
                }
            };
            if (compared_to) {
                patch_arguments['order'] = getOrderArgumentsFromComparedTo(dropped_item_id, compared_to);
            }

            return rest.one('kanban', kanban_id)
                .all('backlog')
                .patch(patch_arguments);
        }

        function moveInArchive(kanban_id, dropped_item_id, compared_to) {
            var patch_arguments = {
                add: {
                    ids : [dropped_item_id]
                }
            };
            if (compared_to) {
                patch_arguments['order'] = getOrderArgumentsFromComparedTo(dropped_item_id, compared_to);
            }

            return rest.one('kanban', kanban_id)
                .all('archive')
                .patch(patch_arguments);
        }

        function moveInColumn(kanban_id, column_id, dropped_item_id, compared_to) {
            var patch_arguments = {
                add: {
                    ids : [dropped_item_id]
                }
            };
            if (compared_to) {
                patch_arguments['order'] = getOrderArgumentsFromComparedTo(dropped_item_id, compared_to);
            }

            return rest.one('kanban', kanban_id)
                .all('items')
                .patch(
                    patch_arguments,
                    {
                        column_id: column_id
                    }
                );
        }

        function getOrderArgumentsFromComparedTo(dropped_item_id, compared_to) {
            return {
                ids         : [dropped_item_id],
                direction   : compared_to.direction,
                compared_to : compared_to.item_id
            };
        }

        function setWipLimitForColumn(column_id, kanban_id, wip_limit) {
            wip_limit = wip_limit || 0;
            return rest.one('kanban_columns', column_id)
                .patch(
                    {
                        wip_limit: wip_limit
                    },
                    {
                        kanban_id: kanban_id
                    }
                 );
        }

        function updateKanbanLabel(kanban_id, kanban_label) {
            return rest.one('kanban', kanban_id).patch({
                label: kanban_label
            });
        }

        function deleteKanban(kanban_id) {
            return rest.one('kanban', kanban_id).remove();
        }
    }
})();