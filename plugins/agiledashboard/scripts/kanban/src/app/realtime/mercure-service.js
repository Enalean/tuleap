import { post } from "@tuleap/tlp-fetch";
import { RealtimeMercure } from "./realtime-mercure";
import { buildEventDispatcher } from "./buildEventDispatcher";

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
];
function MercureService(
    $timeout,
    $q,
    KanbanColumnService,
    ColumnCollectionService,
    DroppedService,
    SharedPropertiesService,
    jwtHelper,
    KanbanItemRestService
) {
    const self = this;
    let realtime_mercure;
    let realtime_token;
    Object.assign(self, {
        init,
        listenTokenExpired,
        listenKanbanItemUpdate,
        listenKanbanItemMoved,
        listenKanbanItemCreate,
    });
    function init() {
        getToken(SharedPropertiesService.getKanban().id).then((data) => {
            realtime_token = data;
            realtime_mercure = new RealtimeMercure(
                realtime_token,
                "/.well-known/mercure?topic=Kanban/" + SharedPropertiesService.getKanban().id,
                buildEventDispatcher(
                    listenKanbanItemUpdate,
                    listenKanbanItemMoved,
                    listenKanbanItemCreate
                )
            );
            listenTokenExpired();
        });
    }
    function getToken(id) {
        return $q.when(
            post(encodeURI("mercure_realtime_token/" + id)).then((response) => response.text())
        );
    }
    function listenTokenExpired() {
        var expired_date = new Date(jwtHelper.getTokenExpirationDate(realtime_token) - 30 * 1000);
        var timeout = Math.abs(new Date() - expired_date);
        if (expired_date < 0) {
            requestJWTToRefreshToken();
        } else {
            $timeout(() => {
                requestJWTToRefreshToken();
            }, timeout);
        }
    }
    function requestJWTToRefreshToken() {
        getToken(SharedPropertiesService.getKanban().id).then((data) => {
            realtime_token = data;
            realtime_mercure.editToken(realtime_token);
            listenTokenExpired();
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
}
