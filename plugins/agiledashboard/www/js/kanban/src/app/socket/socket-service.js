import _ from 'lodash';

export default SocketService;

SocketService.$inject = [
    '$timeout',
    '$q',
    'moment',
    'locker',
    'SocketFactory',
    'KanbanService',
    'KanbanColumnService',
    'ColumnCollectionService',
    'DroppedService',
    'SharedPropertiesService',
    'JWTService'
];

function SocketService(
    $timeout,
    $q,
    moment,
    locker,
    SocketFactory,
    KanbanService,
    KanbanColumnService,
    ColumnCollectionService,
    DroppedService,
    SharedPropertiesService,
    JWTService
) {
    var self = this;

    _.extend(self, {
        checkDisconnect         : {
            disconnect: false
        },
        listenTokenExpired      : listenTokenExpired,
        listenNodeJSServer      : listenNodeJSServer,
        listenKanbanItemCreate  : listenKanbanItemCreate,
        listenKanbanItemMove    : listenKanbanItemMove,
        listenKanbanItemEdit    : listenKanbanItemEdit,
        listenKanbanColumnCreate: listenKanbanColumnCreate,
        listenKanbanColumnMove  : listenKanbanColumnMove,
        listenKanbanColumnEdit  : listenKanbanColumnEdit,
        listenKanbanColumnDelete: listenKanbanColumnDelete,
        listenKanban            : listenKanban
    });

    function listenTokenExpired() {
        var expired_date = moment(locker.get('token-expired-date')).subtract(5, 'm');
        var timeout      = expired_date.diff(moment());
        if (timeout < 0) {
            requestJWTToRefreshToken();
        } else {
            $timeout(function () {
                requestJWTToRefreshToken();
            }, timeout);
        }
    }

    function listenNodeJSServer() {
        if (SharedPropertiesService.getNodeServerAddress()) {
            listenToDisconnect();
            listenToError();
            return JWTService.getJWT().then(function (data) {
                locker.put('token', data.token);
                locker.put('token-expired-date', JWTService.getTokenExpiredDate(data.token));
                return subscribe();
            });
        } else {
            return $q.reject('No server Node.js.');
        }
    }

    function subscribe() {
        var kanban = SharedPropertiesService.getKanban();
        if(kanban) {
            SocketFactory.emit('subscription', {
                nodejs_server_version: SharedPropertiesService.getNodeServerVersion(),
                token                : locker.get('token'),
                room_id              : kanban.id,
                uuid                 : SharedPropertiesService.getUUID()
            });
        }
    }

    function refreshToken() {
        SocketFactory.emit('token', {
            token: locker.get('token')
        });
    }

    function listenToDisconnect() {
        SocketFactory.on('disconnect', function() {
            self.checkDisconnect.disconnect = true;
        });
    }

    function listenToError() {
        SocketFactory.on('error-jwt', function(error) {
            if(error === 'JWTExpired') {
                JWTService.getJWT().then(function (data) {
                    locker.put('token', data.token);
                    subscribe();
                });
            }
        });
    }

    function requestJWTToRefreshToken() {
        JWTService.getJWT().then(function (data) {
            locker.put('token', data.token);
            locker.put('token-expired-date', JWTService.getTokenExpiredDate(data.token));
            refreshToken();
            listenTokenExpired();
        });
    }

    function listenKanbanItemCreate() {
        /**
         * Data received looks like:
         * {
         *   artifact: {
         *          id: 79584,
         *          item_name: 'kanbantask',
         *          label: 'Documentation API',
         *          color: 'inca_silver',
         *          card_fields: [
         *              {
         *                  field_id: 15261,
         *                  type: 'msb',
         *                  label: 'Assigned to',
         *                  values: [Object],
         *                  bind_value_ids: [Object]
         *              }
         *          ],
         *          timeinfo: {
         *                      kanban: null,
         *                      archive: null
         *                    },
         *          in_column: 'backlog'
         *    }
         *  }
         *
         */
        SocketFactory.on('kanban_item:create', function(data) {
            _.extend(data.artifact, {
                updating    : false,
                is_collapsed: SharedPropertiesService.doesUserPrefersCompactCards()
            });

            var column      = ColumnCollectionService.getColumn(data.artifact.in_column),
                compared_to = DroppedService.getComparedToBeLastItemOfColumn(column);

            KanbanColumnService.addItem(
                data.artifact,
                column,
                compared_to
            );
        });
    }

    function listenKanbanItemMove() {
        /**
         * Data received looks like:
         *  {
         *      order: {
         *          ids: [79213],
         *          direction: 'before',
         *          compared_to: 79790
         *      },
         *      add: {
         *          ids: [79213]
         *      },
         *      in_column: 6816
         *  }
         *
         */
        SocketFactory.on('kanban_item:move', function(data) {
            var ids         = data.add ? data.add.ids : data.order.ids,
                compared_to = null;

            if (data.order) {
                compared_to = {
                    direction: data.order.direction,
                    item_id  : data.order.compared_to
                };
            }

            _.forEach(ids, function(id) {
                var source_column      = ColumnCollectionService.getColumn(data.from_column),
                    destination_column = ColumnCollectionService.getColumn(data.in_column);
                KanbanColumnService.findAndMoveItem(id, source_column, destination_column, compared_to);
            });
        });
    }

    function listenKanbanItemEdit() {
        /**
         * Data received looks like:
         * {
         *   artifact: {
         *          id: 79584,
         *          item_name: 'kanbantask',
         *          label: 'Documentation API',
         *          color: 'inca_silver',
         *          card_fields: [
         *              {
         *                  field_id: 15261,
         *                  type: 'msb',
         *                  label: 'Assigned to',
         *                  values: [Object],
         *                  bind_value_ids: [Object]
         *              }
         *          ],
         *          timeinfo: {
         *                      kanban: null,
         *                      archive: null
         *                    },
         *          in_column: 'backlog'
         *    },
         *    index: 2
         *  }
         */
        SocketFactory.on('kanban_item:edit', function(data) {
            var item               = ColumnCollectionService.findItemById(data.artifact.id),
                destination_column = ColumnCollectionService.getColumn(data.artifact.in_column);

            _.extend(data.artifact, {
                updating    : false,
                is_collapsed: item ? item.is_collapsed : SharedPropertiesService.doesUserPrefersCompactCards()
            });

            if (item) {
                KanbanColumnService.updateItemContent(item, data.artifact);
            }

            KanbanColumnService.filterItems(destination_column);
        });
    }

    function listenKanbanColumnCreate() {
        /**
         * Data received looks like:
         * {
         *      color: null
         *      id: 15343
         *      label: "test"
         *      limit: null
         *      limit_input: null
         *      user_can_add_in_place: true
         *      user_can_edit_label: true
         *      user_can_remove_column: true
         *      wip_in_edit: false
         *
         *      ...
         * }
         */
        SocketFactory.on('kanban_column:create', function (data) {
            ColumnCollectionService.addColumn(data);
        });
    }

    function listenKanbanColumnMove() {
        /**
         * Data received looks like:
         * [15333, 15334, 15335, 15338]
         */
        SocketFactory.on('kanban_column:move', function (data) {
            ColumnCollectionService.reorderColumns(data);
        });
    }

    function listenKanbanColumnEdit() {
        /**
         * Data received looks like:
         * {
         *      id: 15343,
         *      label: "test",
         *      wip_limit: 0
         * }
         */
        SocketFactory.on('kanban_column:edit', function (data) {
            var column = ColumnCollectionService.getColumn(data.id);

            if(column) {
                column.label     = data.label;
                column.limit     = data.wip_limit;
                column.wip_limit = data.wip_limit;
            }
        });
    }

    function listenKanbanColumnDelete() {
        /**
         * Data received looks like: 15233
         */
        SocketFactory.on('kanban_column:delete', function (data) {
            ColumnCollectionService.removeColumn(data);
        });
    }

    function listenKanban() {
        /**
         * Data received looks like: "New Kanban Name"
         */
        SocketFactory.on('kanban:edit', function (data) {
            KanbanService.updateKanbanName(data);
        });

        /**
         * No data received
         */
        SocketFactory.on('kanban:delete', function () {
            KanbanService.removeKanban();
        });
    }
}
