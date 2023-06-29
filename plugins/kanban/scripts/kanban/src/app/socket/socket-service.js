import { setError } from "../feedback-state.js";

export default SocketService;

SocketService.$inject = [
    "$timeout",
    "$q",
    "moment",
    "locker",
    "gettextCatalog",
    "SocketFactory",
    "KanbanService",
    "KanbanColumnService",
    "ColumnCollectionService",
    "DroppedService",
    "SharedPropertiesService",
    "JWTService",
    "KanbanFilteredUpdatedAlertService",
    "KanbanItemRestService",
];

function SocketService(
    $timeout,
    $q,
    moment,
    locker,
    gettextCatalog,
    SocketFactory,
    KanbanService,
    KanbanColumnService,
    ColumnCollectionService,
    DroppedService,
    SharedPropertiesService,
    JWTService,
    KanbanFilteredUpdatedAlertService,
    KanbanItemRestService
) {
    const self = this;

    Object.assign(self, {
        listenTokenExpired,
        listenNodeJSServer,
        listenKanbanFilteredUpdate,
        listenKanbanItemCreate,
        listenKanbanItemMove,
        listenKanbanItemEdit,
        listenKanbanColumnCreate,
        listenKanbanColumnMove,
        listenKanbanColumnEdit,
        listenKanbanColumnDelete,
        listenKanban,
        open,
    });

    function open() {
        SocketFactory.connect();
    }
    function listenTokenExpired() {
        var expired_date = moment(locker.get("token-expired-date")).subtract(5, "m");
        var timeout = expired_date.diff(moment());
        if (timeout < 0) {
            requestJWTToRefreshToken();
        } else {
            $timeout(() => {
                requestJWTToRefreshToken();
            }, timeout);
        }
    }

    function listenNodeJSServer() {
        listenToDisconnect();
        listenToError();
        return JWTService.getJWT().then((data) => {
            locker.put("token", data.token);
            locker.put("token-expired-date", JWTService.getTokenExpiredDate(data.token));
            return subscribe();
        });
    }

    function subscribe() {
        var kanban = SharedPropertiesService.getKanban();
        if (kanban) {
            SharedPropertiesService.setIsNodeServerConnected(true);
            SocketFactory.emit("subscription", {
                nodejs_server_version: SharedPropertiesService.getNodeServerVersion(),
                token: locker.get("token"),
                room_id: kanban.id,
                uuid: SharedPropertiesService.getUUID(),
            });
        }
    }

    function refreshToken() {
        SocketFactory.emit("token", {
            token: locker.get("token"),
        });
    }

    function listenToDisconnect() {
        SocketFactory.on("disconnect", () => {
            setError(
                gettextCatalog.getString(
                    "You are disconnected from real time. Please reload your page."
                )
            );
            SharedPropertiesService.setIsNodeServerConnected(false);
        });
    }

    function listenToError() {
        SocketFactory.on("error-jwt", (error) => {
            if (error === "JWTExpired") {
                JWTService.getJWT().then((data) => {
                    locker.put("token", data.token);
                    subscribe();
                });
            }
        });
    }

    function requestJWTToRefreshToken() {
        JWTService.getJWT().then((data) => {
            locker.put("token", data.token);
            locker.put("token-expired-date", JWTService.getTokenExpiredDate(data.token));
            refreshToken();
            listenTokenExpired();
        });
    }

    function listenKanbanFilteredUpdate() {
        SocketFactory.on("kanban_item:create", () => {
            KanbanFilteredUpdatedAlertService.setCardHasBeenUpdated();
        });

        SocketFactory.on("kanban_item:update", () => {
            KanbanFilteredUpdatedAlertService.setCardHasBeenUpdated();
        });

        SocketFactory.on("kanban_item:move", () => {
            KanbanFilteredUpdatedAlertService.setCardHasBeenUpdated();
        });
    }

    function listenKanbanItemCreate() {
        /**
         * Data received looks like:
         * {
         *   artifact_id: 321
         * }
         */
        SocketFactory.on("kanban_item:create", ({ artifact_id }) => {
            KanbanItemRestService.getItem(artifact_id).then((new_item) => {
                if (!new_item) {
                    return;
                }

                Object.assign(new_item, {
                    updating: false,
                    is_collapsed: SharedPropertiesService.doesUserPrefersCompactCards(),
                    created: true,
                });

                setTimeout(() => {
                    new_item.created = false;
                }, 1000);

                const column = ColumnCollectionService.getColumn(new_item.in_column),
                    compared_to = DroppedService.getComparedToBeLastItemOfColumn(column);

                KanbanColumnService.addItem(new_item, column, compared_to);
                KanbanColumnService.filterItems(column);
            });
        });
    }

    function listenKanbanItemMove() {
        /**
         * Data received looks like:
         *  {
         *      artifact_id: 321,
         *      items_ids: [302, 321, 562]
         *      in_column: 6816,
         *      from_column: 6580
         *  }
         *
         */
        SocketFactory.on("kanban_item:move", (data) => {
            const source_column = ColumnCollectionService.getColumn(data.from_column),
                destination_column = ColumnCollectionService.getColumn(data.in_column);

            if (!source_column || !destination_column) {
                return;
            }

            KanbanColumnService.findItemAndReorderItems(
                data.artifact_id,
                source_column,
                destination_column,
                data.ordered_destination_column_items_ids
            );
        });
    }

    function listenKanbanItemEdit() {
        /**
         * Data received looks like:
         * {
         *   artifact: 321
         *  }
         */
        SocketFactory.on("kanban_item:update", ({ artifact_id }) => {
            const item = ColumnCollectionService.findItemById(artifact_id);

            if (!item) {
                return;
            }

            KanbanItemRestService.getItem(artifact_id).then((new_item) => {
                if (!new_item) {
                    return;
                }

                Object.assign(new_item, {
                    updating: false,
                    is_collapsed: item.is_collapsed,
                });

                const destination_column = ColumnCollectionService.getColumn(new_item.in_column);
                KanbanColumnService.updateItemContent(item, new_item);
                KanbanColumnService.filterItems(destination_column);
            });
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
        SocketFactory.on("kanban_column:create", (data) => {
            ColumnCollectionService.addColumn(data);
        });
    }

    function listenKanbanColumnMove() {
        /**
         * Data received looks like:
         * [15333, 15334, 15335, 15338]
         */
        SocketFactory.on("kanban_column:move", (data) => {
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
        SocketFactory.on("kanban_column:edit", (data) => {
            var column = ColumnCollectionService.getColumn(data.id);

            if (column) {
                column.label = data.label;
                column.limit = data.wip_limit;
                column.wip_limit = data.wip_limit;
            }
        });
    }

    function listenKanbanColumnDelete() {
        /**
         * Data received looks like: 15233
         */
        SocketFactory.on("kanban_column:delete", (data) => {
            ColumnCollectionService.removeColumn(data);
        });
    }

    function listenKanban() {
        /**
         * Data received looks like: "New Kanban Name"
         */
        SocketFactory.on("kanban:edit", (data) => {
            KanbanService.updateKanbanName(data);
        });

        /**
         * No data received
         */
        SocketFactory.on("kanban:delete", () => {
            KanbanService.removeKanban();
        });
    }
}
