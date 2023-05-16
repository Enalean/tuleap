import { post } from "@tuleap/tlp-fetch";
import { RealtimeMercure, RetriableError, FatalError } from "./realtime-mercure";
import { buildEventDispatcher } from "./buildEventDispatcher";
import { get } from "@tuleap/tlp-fetch";
import { resetError, setError } from "../feedback-state";
export default MercureService;

MercureService.$inject = [
    "$timeout",
    "$q",
    "KanbanColumnService",
    "ColumnCollectionService",
    "DroppedService",
    "SharedPropertiesService",
    "jwtHelper",
    "KanbanItemRestService",
    "KanbanService",
    "gettextCatalog",
];
function MercureService(
    $timeout,
    $q,
    KanbanColumnService,
    ColumnCollectionService,
    DroppedService,
    SharedPropertiesService,
    jwtHelper,
    KanbanItemRestService,
    KanbanService,
    gettextCatalog
) {
    const self = this;
    let realtime_mercure;
    let realtime_token;
    let loadColumnsFunction;
    let mercureRetryNumber = 0;
    Object.assign(self, {
        init,
        listenKanbanItemUpdate,
        listenKanbanItemMoved,
        listenKanbanItemCreate,
        getKanban,
        updateKanban,
        getToken,
        checkResponseKanban,
    });
    function init(loadColumns) {
        this.getToken(SharedPropertiesService.getKanban().id).then((data) => {
            realtime_token = data;
            realtime_mercure = new RealtimeMercure(
                realtime_token,
                "/.well-known/mercure?topic=Kanban/" + SharedPropertiesService.getKanban().id,
                buildEventDispatcher(
                    listenKanbanItemUpdate,
                    listenKanbanItemMoved,
                    listenKanbanItemCreate,
                    listenKanbanStructuralUpdate
                ),
                errCallback,
                sucessCallback
            );
        });
        loadColumnsFunction = loadColumns;
    }
    function getToken(id) {
        return $q.when(
            post(encodeURI("mercure_realtime_token/" + id)).then((response) => response.text())
        );
    }
    function requestJWTToRefreshToken() {
        getToken(SharedPropertiesService.getKanban().id).then((data) => {
            realtime_token = data;
            realtime_mercure.editToken(realtime_token);
        });
    }

    function listenKanbanItemUpdate(event) {
        const item = ColumnCollectionService.findItemById(event.data.artifact_id);

        if (!item) {
            return;
        }
        KanbanItemRestService.getItem(event.data.artifact_id).then((new_item) => {
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
    }
    function listenKanbanItemCreate(event) {
        KanbanItemRestService.getItem(event.data.artifact_id).then((new_item) => {
            if (!new_item) {
                return;
            }
            const column = ColumnCollectionService.getColumn(new_item.in_column);
            const copy_array_id = column.content.map((x) => x.id);
            let flag_pending = false;
            copy_array_id.forEach((id) => {
                if (id === undefined) {
                    flag_pending = true;
                }
            });
            if (flag_pending) {
                $timeout(() => {
                    listenKanbanItemCreate(event);
                }, 500);
                return;
            }
            const already_existing_item = ColumnCollectionService.findItemById(new_item.id);
            if (already_existing_item) {
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

            const compared_to = DroppedService.getComparedToBeLastItemOfColumn(column);
            if (!column.content.find((element) => element.id === new_item.id)) {
                KanbanColumnService.addItem(new_item, column, compared_to);
                KanbanColumnService.filterItems(column);
            }
        });
    }
    function listenKanbanItemMoved(event) {
        const source_column = ColumnCollectionService.getColumn(event.data.from_column),
            destination_column = ColumnCollectionService.getColumn(event.data.in_column);

        if (!source_column || !destination_column) {
            return;
        }
        KanbanColumnService.findItemAndReorderItemMercure(
            event.data.artifact_id,
            source_column,
            destination_column,
            event.data.ordered_destination_column_items_ids
        );
    }
    function callLoadColumn() {
        loadColumnsFunction();
    }
    function getKanban() {
        return $q.when(
            get(encodeURI("/api/v1/kanban/" + SharedPropertiesService.getKanban().id)).then(
                (response) => {
                    return response.json();
                }
            )
        );
    }
    function listenKanbanStructuralUpdate() {
        const kanban = getKanban();
        checkResponseKanban(kanban);
    }

    function checkResponseKanban(response) {
        return response
            .then((kanban) => {
                updateKanban(kanban);
            })
            .catch((error) => {
                if (error.response !== undefined && error.response.status === 404) {
                    KanbanService.removeKanban();
                } else {
                    throw error;
                }
            });
    }
    function updateKanban(kanban) {
        KanbanService.updateKanbanName(kanban.label);
        SharedPropertiesService.getKanban().columns.length = 0;
        SharedPropertiesService.getKanban().columns.splice(
            0,
            kanban.columns.length,
            ...kanban.columns
        );
        callLoadColumn();
    }
    function errCallback(err) {
        realtime_mercure.abortConnection();
        if (mercureRetryNumber > 1) {
            setError(
                gettextCatalog.getString(
                    "You are disconnected from real time. Please reload your page."
                )
            );
        }
        if (err instanceof RetriableError || err instanceof FatalError) {
            let timeout = Math.pow(2, mercureRetryNumber) * 1000 + Math.floor(Math.random() * 1000);
            setTimeout(requestJWTToRefreshToken, timeout);
            mercureRetryNumber = mercureRetryNumber + 1;
        }
    }
    function sucessCallback() {
        if (mercureRetryNumber > 1) {
            resetError();
        }
        mercureRetryNumber = 0;
    }
}
