import "./error-modal/error.tpl.html";
import { setError, resetError } from "./feedback-state.js";

import _ from "lodash";
import angular from "angular";

export default KanbanCtrl;

KanbanCtrl.$inject = [
    "$q",
    "$scope",
    "gettextCatalog",
    "SharedPropertiesService",
    "KanbanService",
    "KanbanItemRestService",
    "NewTuleapArtifactModalService",
    "UserPreferencesService",
    "SocketService",
    "KanbanColumnService",
    "ColumnCollectionService",
    "DroppedService",
    "KanbanFilterValue",
    "TlpModalService",
    "RestErrorService",
    "FilterTrackerReportService",
];

function KanbanCtrl(
    $q,
    $scope,
    gettextCatalog,
    SharedPropertiesService,
    KanbanService,
    KanbanItemRestService,
    NewTuleapArtifactModalService,
    UserPreferencesService,
    SocketService,
    KanbanColumnService,
    ColumnCollectionService,
    DroppedService,
    KanbanFilterValue,
    TlpModalService,
    RestErrorService,
    FilterTrackerReportService
) {
    var self = this,
        limit = 50,
        offset = 0,
        kanban = SharedPropertiesService.getKanban(),
        user_id = SharedPropertiesService.getUserId();

    self.backlog = Object.assign(kanban.backlog, {
        id: "backlog",
        content: [],
        nb_items_at_kanban_init: 0,
        filtered_content: [],
        loading_items: true,
        fully_loaded: false,
    });
    self.archive = Object.assign(kanban.archive, {
        id: "archive",
        content: [],
        nb_items_at_kanban_init: 0,
        filtered_content: [],
        loading_items: true,
        fully_loaded: false,
    });

    Object.assign(self, {
        kanban,
        board: {
            columns: kanban.columns,
        },
        user_prefers_collapsed_cards: true,
        is_report_loading: false,
        is_edit_loading: false,
        $onInit: init,
        isColumnWipReached,
        userIsAdmin,
        createItemInPlace,
        createItemInPlaceInBacklog,
        editKanban,
        showEditbutton,
        expandColumn,
        toggleColumn,
        expandBacklog,
        toggleBacklog,
        expandArchive,
        toggleArchive,
        setIsCollapsed,
        filterCards,
        showEditModal,
        moveItemAtTheEnd,
        saveCardsViewMode,
        moveKanbanItemToTop,
        moveKanbanItemToBottom,
        openReportModal,
        addKanbanToMyDashboard,
        reflowKustomScrollBars,
        displayCardsAndWIPNotUpdated: FilterTrackerReportService.areNotCardsAndWIPUpdated,
        displayWIPNotUpdated: FilterTrackerReportService.isNotWIPUpdated,
        filter: KanbanFilterValue,
        loading_modal: NewTuleapArtifactModalService.loading,
    });

    function init() {
        initViewMode();
        initFilter();
        loadColumns();
        loadBacklog(limit, offset);
        loadArchive(limit, offset);
        SocketService.listenNodeJSServer()
            .then(function () {
                if (FilterTrackerReportService.isFiltersTrackerReportSelected()) {
                    SocketService.listenKanbanFilteredUpdate();
                } else {
                    SocketService.listenKanbanItemCreate();
                    SocketService.listenKanbanItemEdit();
                    SocketService.listenKanbanItemMove();
                }
                SocketService.listenKanbanColumnCreate();
                SocketService.listenKanbanColumnMove();
                SocketService.listenKanbanColumnEdit();
                SocketService.listenKanbanColumnDelete();
                SocketService.listenKanban();
                SocketService.listenTokenExpired();
            })
            .catch(() => {
                // ignore the fact that there is no nodejs server
            });
    }

    function initViewMode() {
        self.user_prefers_collapsed_cards = SharedPropertiesService.doesUserPrefersCompactCards();
    }

    function initFilter() {
        angular.element(".kanban-header-search").on("input", function (event) {
            self.filter.terms = event.target.value;
            filterCards();
        });
    }

    function saveCardsViewMode() {
        SharedPropertiesService.setUserPrefersCompactCards(self.user_prefers_collapsed_cards);
        UserPreferencesService.setPreference(
            user_id,
            "agiledashboard_kanban_item_view_mode_" + kanban.id,
            SharedPropertiesService.getViewMode()
        );

        self.backlog.content.forEach(forceIsCollapsed);
        self.archive.content.forEach(forceIsCollapsed);
        self.board.columns.forEach(function (column) {
            column.content.forEach(forceIsCollapsed);
        });

        self.reflowKustomScrollBars();

        function forceIsCollapsed(item) {
            setIsCollapsed(item, self.user_prefers_collapsed_cards);
        }
    }

    function setIsCollapsed(item, is_collapsed) {
        item.is_collapsed = is_collapsed;
    }

    function filterCards() {
        if (self.backlog.is_open) {
            filterBacklogCards();
        }
        if (self.archive.is_open) {
            filterArchiveCards();
        }

        _(self.board.columns).filter("is_open").forEach(filterColumnCards);

        reflowKustomScrollBars();
    }

    function filterBacklogCards() {
        KanbanColumnService.filterItems(self.backlog);
    }

    function filterArchiveCards() {
        KanbanColumnService.filterItems(self.archive);
    }

    function filterColumnCards(column) {
        KanbanColumnService.filterItems(column);
    }

    function reflowKustomScrollBars() {
        $scope.$broadcast("rebuild:kustom-scroll");
    }

    function collapseColumn(column) {
        if (column.is_open) {
            emptyArray(column.filtered_content);
            KanbanService.collapseColumn(kanban.id, column.id);
            column.is_open = false;
        }
    }

    function expandColumn(column) {
        if (column.is_open) {
            return;
        }

        KanbanService.expandColumn(kanban.id, column.id);
        column.is_open = true;

        if (!column.fully_loaded) {
            loadColumnContent(column, limit, offset);
        } else {
            filterColumnCards(column);
        }
    }

    function toggleColumn(column) {
        if (column.is_open) {
            collapseColumn(column);
        } else {
            expandColumn(column);
        }

        reflowKustomScrollBars();
    }

    function collapseBacklog() {
        if (self.backlog.is_open) {
            emptyArray(self.backlog.filtered_content);
            KanbanService.collapseBacklog(kanban.id);
            self.backlog.is_open = false;
        }
    }

    function expandBacklog() {
        if (self.backlog.is_open) {
            return;
        }

        KanbanService.expandBacklog(kanban.id);
        self.backlog.is_open = true;

        if (!self.backlog.fully_loaded) {
            loadBacklogContent(limit, offset);
        } else {
            filterBacklogCards();
        }
    }

    function toggleBacklog() {
        if (self.backlog.is_open) {
            collapseBacklog();
        } else {
            KanbanService.expandBacklog(kanban.id);
            expandBacklog();
        }

        reflowKustomScrollBars();
    }

    function collapseArchive() {
        if (self.archive.is_open) {
            emptyArray(self.archive.filtered_content);
            KanbanService.collapseArchive(kanban.id);
            self.archive.is_open = false;
        }
    }

    function expandArchive() {
        if (self.archive.is_open) {
            return;
        }

        KanbanService.expandArchive(kanban.id);
        self.archive.is_open = true;

        if (!self.archive.fully_loaded) {
            loadArchiveContent(limit, offset);
        } else {
            filterArchiveCards();
        }
    }

    function toggleArchive() {
        if (self.archive.is_open) {
            collapseArchive();
        } else {
            expandArchive();
        }

        reflowKustomScrollBars();
    }

    function emptyArray(array) {
        array.length = 0;
    }

    function reload(response) {
        RestErrorService.reload(response);
    }

    function showEditbutton() {
        return userIsAdmin();
    }

    function editKanban() {
        self.is_edit_loading = true;
        resetError();
        $q.when(import(/* webpackChunkName: "edit-modal" */ "./edit-kanban/edit-kanban.js"))
            .then((module) => {
                const { default: controller } = module;

                TlpModalService.open({
                    templateUrl: "edit-kanban.tpl.html",
                    controller,
                    controllerAs: "edit_modal",
                    resolve: {
                        rebuild_scrollbars: reflowKustomScrollBars,
                    },
                });
            })
            .catch(() => {
                setError(
                    gettextCatalog.getString(
                        "There was an error while loading the Edit Kanban modal. Please check your network connection."
                    )
                );
            })
            .finally(() => {
                self.is_edit_loading = false;
            });
    }

    function openReportModal() {
        self.is_report_loading = true;
        resetError();
        $q.when(import(/* webpackChunkName: "reports-modal" */ "./reports-modal/reports-modal.js"))
            .then((module) => {
                const { default: controller } = module;

                TlpModalService.open({
                    templateUrl: "reports-modal.tpl.html",
                    controller,
                    controllerAs: "reports_modal",
                });
            })
            .catch(() => {
                setError(
                    gettextCatalog.getString(
                        "There was an error while loading the Reports modal. Please check your network connection."
                    )
                );
            })
            .finally(() => {
                self.is_report_loading = false;
            });
    }

    function loadColumns() {
        kanban.columns.forEach(function (column) {
            ColumnCollectionService.augmentColumn(column);

            if (column.is_open) {
                loadColumnContent(column, limit, offset);
            } else {
                KanbanService.getColumnContentSize(kanban.id, column.id).then(function (size) {
                    column.loading_items = false;
                    column.nb_items_at_kanban_init = size;
                });
            }
        });
    }

    function loadColumnContent(column, limit, offset) {
        column.loading_items = true;

        return KanbanService.getItems(kanban.id, column.id, limit, offset).then(function (data) {
            column.content = column.content.concat(data.results);

            if (column.is_open) {
                filterColumnCards(column);

                reflowKustomScrollBars();
            }

            if (offset + limit < data.total) {
                loadColumnContent(column, limit, offset + limit);
            } else {
                column.loading_items = false;
                column.fully_loaded = true;
            }
        });
    }

    function loadBacklog(limit, offset) {
        if (self.backlog.is_open) {
            loadBacklogContent(limit, offset);
        } else {
            KanbanService.getBacklogSize(kanban.id).then(function (size) {
                self.backlog.loading_items = false;
                self.backlog.nb_items_at_kanban_init = size;
            });
        }
    }

    function loadBacklogContent(limit, offset) {
        self.backlog.loading_items = true;

        return KanbanService.getBacklog(kanban.id, limit, offset).then(function (data) {
            self.backlog.content = self.backlog.content.concat(data.results);

            if (self.backlog.is_open) {
                filterBacklogCards();

                reflowKustomScrollBars();
            }

            if (offset + limit < data.total) {
                loadBacklogContent(limit, offset + limit);
            } else {
                self.backlog.loading_items = false;
                self.backlog.fully_loaded = true;
            }
        });
    }

    function loadArchive(limit, offset) {
        if (self.archive.is_open) {
            loadArchiveContent(limit, offset);
        } else {
            KanbanService.getArchiveSize(kanban.id).then(function (size) {
                self.archive.loading_items = false;
                self.archive.nb_items_at_kanban_init = size;
            });
        }
    }

    function loadArchiveContent(limit, offset) {
        self.archive.loading_items = true;

        return KanbanService.getArchive(kanban.id, limit, offset).then(function (data) {
            self.archive.content = self.archive.content.concat(data.results);

            if (self.archive.is_open) {
                filterArchiveCards();

                reflowKustomScrollBars();
            }

            if (offset + limit < data.total) {
                loadArchiveContent(limit, offset + limit);
            } else {
                self.archive.loading_items = false;
                self.archive.fully_loaded = true;
            }
        });
    }

    function isColumnWipReached(column) {
        return (
            (FilterTrackerReportService.areCardsAndWIPUpdated() ||
                FilterTrackerReportService.isWIPUpdated()) &&
            column.limit &&
            column.limit < column.content.length
        );
    }

    function userIsAdmin() {
        return SharedPropertiesService.getUserIsAdmin();
    }

    function createItemInPlaceInBacklog(label) {
        var item = {
            label: label,
            updating: true,
            is_collapsed: SharedPropertiesService.doesUserPrefersCompactCards(),
        };

        self.backlog.content.push(item);
        self.backlog.filtered_content.push(item);

        KanbanItemRestService.createItemInBacklog(kanban.id, item.label).then(function (response) {
            item.updating = false;
            _.extend(item, response.data);

            reflowKustomScrollBars();
        }, reload);
    }

    function createItemInPlace(label, column) {
        var item = {
            label: label,
            updating: true,
            is_collapsed: SharedPropertiesService.doesUserPrefersCompactCards(),
        };

        column.content.push(item);
        column.filtered_content.push(item);

        KanbanItemRestService.createItem(kanban.id, column.id, item.label).then(function (
            response
        ) {
            item.updating = false;
            _.extend(item, response.data);

            reflowKustomScrollBars();
        },
        reload);
    }

    function showEditModal($event, item) {
        var when_left_mouse_click = 1;

        if ($event.which === when_left_mouse_click) {
            $event.preventDefault();

            var callback = function (artifact_id) {
                item.updating = true;

                return KanbanItemRestService.getItem(artifact_id).then(function (data) {
                    updateItemMoveAtTheEnd(item, data);
                });
            };

            NewTuleapArtifactModalService.showEdition(
                SharedPropertiesService.getUserId(),
                kanban.tracker_id,
                item.id,
                callback
            );
        }
    }

    function updateItemMoveAtTheEnd(item, item_updated) {
        if (checkColumnChanged(item, item_updated)) {
            self.moveItemAtTheEnd(item, item_updated.in_column);
        } else {
            item.updating = false;
        }
        KanbanColumnService.updateItemContent(item, item_updated);
    }

    function checkColumnChanged(item, updated_item) {
        var previous_column = ColumnCollectionService.getColumn(item.in_column),
            new_column = ColumnCollectionService.getColumn(updated_item.in_column);
        return previous_column !== new_column;
    }

    function moveItemAtTheEnd(item, column_id) {
        var source_column = ColumnCollectionService.getColumn(item.in_column),
            destination_column = ColumnCollectionService.getColumn(column_id),
            compared_to = DroppedService.getComparedToBeLastItemOfColumn(destination_column);

        item.updating = true;

        var promise = DroppedService.moveToColumn(
            kanban.id,
            column_id,
            item.id,
            compared_to,
            item.in_column
        ).then(function () {
            item.updating = false;
            KanbanColumnService.moveItem(item, source_column, destination_column, compared_to);
        });

        return promise;
    }

    function moveKanbanItemToTop(item) {
        var column = ColumnCollectionService.getColumn(item.in_column),
            compared_to = DroppedService.getComparedToBeFirstItemOfColumn(column);

        KanbanColumnService.moveItem(item, column, column, compared_to);
        DroppedService.reorderColumn(kanban.id, column.id, item.id, compared_to);
    }

    function moveKanbanItemToBottom(item) {
        var column = ColumnCollectionService.getColumn(item.in_column),
            compared_to = DroppedService.getComparedToBeLastItemOfColumn(column);

        KanbanColumnService.moveItem(item, column, column, compared_to);
        DroppedService.reorderColumn(kanban.id, column.id, item.id, compared_to);
    }

    function addKanbanToMyDashboard() {
        KanbanService.addKanbanToMyDashboard();
    }
}
