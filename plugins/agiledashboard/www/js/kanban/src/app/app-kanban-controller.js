import './reports-modal/reports-modal.tpl.html';
import _ from 'lodash';
import { element } from 'angular';
import { dropdown, modal } from 'tlp';

export default KanbanCtrl;

KanbanCtrl.$inject = [
    '$scope',
    '$modal',
    '$sce',
    'gettextCatalog',
    'amCalendarFilter',
    'SharedPropertiesService',
    'KanbanService',
    'KanbanItemRestService',
    'NewTuleapArtifactModalService',
    'UserPreferencesService',
    'SocketService',
    'KanbanColumnService',
    'ColumnCollectionService',
    'DroppedService',
    'KanbanFilterValue'
];

function KanbanCtrl(
    $scope,
    $modal,
    $sce,
    gettextCatalog,
    amCalendarFilter,
    SharedPropertiesService,
    KanbanService,
    KanbanItemRestService,
    NewTuleapArtifactModalService,
    UserPreferencesService,
    SocketService,
    KanbanColumnService,
    ColumnCollectionService,
    DroppedService,
    KanbanFilterValue
) {
    var self    = this,
        limit   = 50,
        offset  = 0,
        kanban  = SharedPropertiesService.getKanban(),
        user_id = SharedPropertiesService.getUserId();

    self.kanban  = kanban;
    self.board   = {
        columns: kanban.columns
    };
    self.backlog = _.extend(kanban.backlog, {
        id                     : 'backlog',
        content                : [],
        nb_items_at_kanban_init: 0,
        filtered_content       : [],
        loading_items          : true,
        fully_loaded           : false
    });
    self.archive = _.extend(kanban.archive, {
        id                     : 'archive',
        content                : [],
        nb_items_at_kanban_init: 0,
        filtered_content       : [],
        loading_items          : true,
        fully_loaded           : false
    });
    self.edit_kanban_modal = null;

    self.user_prefers_collapsed_cards = true;
    self.init                         = init;
    self.isColumnWipReached           = isColumnWipReached;
    self.setWipLimitForColumn         = setWipLimitForColumn;
    self.userIsAdmin                  = userIsAdmin;
    self.getTimeInfo                  = getTimeInfo;
    self.getTimeInfoInArchive         = getTimeInfoInArchive;
    self.createItemInPlace            = createItemInPlace;
    self.createItemInPlaceInBacklog   = createItemInPlaceInBacklog;
    self.editKanban                   = editKanban;
    self.showEditbutton               = showEditbutton;
    self.expandColumn                 = expandColumn;
    self.toggleColumn                 = toggleColumn;
    self.expandBacklog                = expandBacklog;
    self.toggleBacklog                = toggleBacklog;
    self.expandArchive                = expandArchive;
    self.toggleArchive                = toggleArchive;
    self.setIsCollapsed               = setIsCollapsed;
    self.filter                       = KanbanFilterValue;
    self.filterCards                  = filterCards;
    self.loading_modal                = NewTuleapArtifactModalService.loading;
    self.showEditModal                = showEditModal;
    self.moveItemAtTheEnd             = moveItemAtTheEnd;
    self.toggleCollapsedMode          = toggleCollapsedMode;
    self.moveKanbanItemToTop          = moveKanbanItemToTop;
    self.moveKanbanItemToBottom       = moveKanbanItemToBottom;
    self.openReportModal              = openReportModal;
    self.addKanbanToMyDashboard       = addKanbanToMyDashboard;
    self.reflowKustomScrollBars       = reflowKustomScrollBars;

    function init() {
        initViewMode();
        loadColumns();
        loadBacklog(limit, offset);
        loadArchive(limit, offset);
        SocketService.listenNodeJSServer().then(function() {
            SocketService.listenKanbanItemCreate();
            SocketService.listenKanbanItemMove();
            SocketService.listenKanbanItemEdit();
            SocketService.listenKanbanColumnCreate();
            SocketService.listenKanbanColumnMove();
            SocketService.listenKanbanColumnEdit();
            SocketService.listenKanbanColumnDelete();
            SocketService.listenKanban();
            SocketService.listenTokenExpired();
        });
    }

    self.init();

    function initViewMode() {
        self.user_prefers_collapsed_cards = SharedPropertiesService.doesUserPrefersCompactCards();
    }

    function toggleCollapsedMode() {
        self.user_prefers_collapsed_cards = !self.user_prefers_collapsed_cards;

        SharedPropertiesService.setUserPrefersCompactCards(self.user_prefers_collapsed_cards);
        UserPreferencesService.setPreference(
            user_id,
            'agiledashboard_kanban_item_view_mode_' + kanban.id,
            SharedPropertiesService.getViewMode()
        );

        self.backlog.content.forEach(forceIsCollapsed);
        self.archive.content.forEach(forceIsCollapsed);
        self.board.columns.forEach(function (column) {
            column.content.forEach(forceIsCollapsed);
        });

        reflowKustomScrollBars();

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

        _(self.board.columns)
            .filter('is_open')
            .forEach(filterColumnCards);

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
        $scope.$broadcast('rebuild:kustom-scroll');
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

        if (! column.fully_loaded) {
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

        if (! self.backlog.fully_loaded) {
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

        if (! self.archive.fully_loaded) {
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
        $modal.open({
            keyboard   : false,
            backdrop   : 'static',
            templateUrl: 'error.tpl.html',
            controller : 'ErrorCtrl as modal',
            resolve    : {
                message: function () {
                    var message = response.status + ' ' + response.statusText;
                    if (response.data.error) {
                        message = response.data.error.code + ' ' + response.data.error.message;
                    }

                    return message;
                }
            }
        });
    }

    function showEditbutton() {
        return userIsAdmin();
    }

    function editKanban() {
        if (self.edit_kanban_modal === null) {
            self.edit_kanban_modal = modal(document.getElementById('edit-kanban-modal'));
        }
        self.edit_kanban_modal.show();
    }

    function openReportModal() {
        $modal.open({
            backdrop    : true,
            templateUrl : 'reports-modal.tpl.html',
            controller  : 'ReportsModalController as reports_modal',
            windowClass : 'reports-modal'
        }).result.catch(
            reloadIfSomethingIsWrong
        );
    }

    function reloadIfSomethingIsWrong(reason) {
        if (reason && reason.status) {
            // the modal's controller dismissed the dialog
            // due to an error in PATCH response and it's passed to us
            // the failing request as a reason so that we can display
            // the error details in the error modal.
            // For more details, see https://angular-ui.github.io/bootstrap/#/modal
            reload(reason);
        }
    }

    function loadColumns() {
        kanban.columns.forEach(function (column) {
            ColumnCollectionService.augmentColumn(column);

            if (column.is_open) {
                loadColumnContent(column, limit, offset);
            } else {
                KanbanService.getColumnContentSize(kanban.id, column.id).then(function(size) {
                    column.loading_items           = false;
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
                column.fully_loaded  = true;
            }
        });
    }

    function loadBacklog(limit, offset) {
        if (self.backlog.is_open) {
            loadBacklogContent(limit, offset);

        } else {
            KanbanService.getBacklogSize(kanban.id).then(function (size) {
                self.backlog.loading_items           = false;
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
                self.backlog.fully_loaded  = true;
            }
        });
    }

    function loadArchive(limit, offset) {
        if (self.archive.is_open) {
            loadArchiveContent(limit, offset);

        } else {
            KanbanService.getArchiveSize(kanban.id).then(function (size) {
                self.archive.loading_items           = false;
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
                self.archive.fully_loaded  = true;
            }
        });
    }

    function isColumnWipReached(column) {
        return (column.limit && column.limit < column.content.length);
    }

    function setWipLimitForColumn(column) {
        column.saving_wip = true;
        return KanbanService.editColumn(kanban.id, column).then(function (data) {
                column.limit = column.limit_input;
                column.wip_in_edit = false;
                column.saving_wip = false;
            },
            reload
        );
    }

    function userIsAdmin() {
        return SharedPropertiesService.getUserIsAdmin();
    }

    function getTimeInfo(column, item) {
        var timeinfo = '';

        if (!column || !item.timeinfo) {
            return;
        }

        timeinfo += getTimeInfoEntry(item.timeinfo.kanban, gettextCatalog.getString('In Kanban since:'));
        timeinfo += "\u000a\u000a";
        timeinfo += getTimeInfoEntry(item.timeinfo[column.id], gettextCatalog.getString('In column since:'));

        return $sce.trustAsHtml(timeinfo);
    }

    function getTimeInfoInArchive(item) {
        var timeinfo = '';

        if (!item.timeinfo) {
            return;
        }

        timeinfo += getTimeInfoEntry(item.timeinfo.kanban, gettextCatalog.getString('In Kanban since:'));
        timeinfo += "\u000a\u000a";
        timeinfo += getTimeInfoEntry(item.timeinfo.archive, gettextCatalog.getString('In column since:'));

        return $sce.trustAsHtml(timeinfo);
    }

    function getTimeInfoEntry(entry_date, label) {
        var timeinfo = '';

        if (entry_date) {
            timeinfo += label + ' ';
            timeinfo += amCalendarFilter(entry_date);
        }

        return timeinfo;
    }

    function createItemInPlaceInBacklog(label) {
        var item = {
            label       : label,
            updating    : true,
            is_collapsed: SharedPropertiesService.doesUserPrefersCompactCards()
        };

        self.backlog.content.push(item);
        self.backlog.filtered_content.push(item);

        KanbanItemRestService.createItemInBacklog(kanban.id, item.label)
            .then(function(response) {
                item.updating = false;
                _.extend(item, response.data);

                reflowKustomScrollBars();
            },
            reload
        );
    }

    function createItemInPlace(label, column) {
        var item = {
            label       : label,
            updating    : true,
            is_collapsed: SharedPropertiesService.doesUserPrefersCompactCards()
        };

        column.content.push(item);
        column.filtered_content.push(item);

        KanbanItemRestService.createItem(kanban.id, column.id, item.label)
            .then(function(response) {
                item.updating = false;
                _.extend(item, response.data);

                reflowKustomScrollBars();
            },
            reload
        );
    }

    function showEditModal($event, item) {
        var when_left_mouse_click = 1;

        if ($event.which === when_left_mouse_click) {
            $event.preventDefault();

            var callback = function(artifact_id) {
                item.updating = true;

                return KanbanItemRestService.getItem(artifact_id).then(function(data) {
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
            new_column      = ColumnCollectionService.getColumn(updated_item.in_column);
        return previous_column !== new_column;
    }

    function moveItemAtTheEnd(item, column_id) {
        var source_column      = ColumnCollectionService.getColumn(item.in_column),
            destination_column = ColumnCollectionService.getColumn(column_id),
            compared_to        = DroppedService.getComparedToBeLastItemOfColumn(destination_column);

        item.updating = true;

        var promise = DroppedService.moveToColumn(
            kanban.id,
            column_id,
            item.id,
            compared_to,
            item.in_column
        ).then(function() {
            item.updating = false;
            KanbanColumnService.moveItem(
                item,
                source_column,
                destination_column,
                compared_to
            );
        });

        return promise;
    }

    function moveKanbanItemToTop(item) {
        var column      = ColumnCollectionService.getColumn(item.in_column),
            compared_to = DroppedService.getComparedToBeFirstItemOfColumn(column);

        KanbanColumnService.moveItem(
            item,
            column,
            column,
            compared_to
        );
        DroppedService.reorderColumn(
            kanban.id,
            column.id,
            item.id,
            compared_to
        );
    }

    function moveKanbanItemToBottom(item) {
        var column      = ColumnCollectionService.getColumn(item.in_column),
            compared_to = DroppedService.getComparedToBeLastItemOfColumn(column);

        KanbanColumnService.moveItem(
            item,
            column,
            column,
            compared_to
        );
        DroppedService.reorderColumn(
            kanban.id,
            column.id,
            item.id,
            compared_to
        );
    }

    function addKanbanToMyDashboard() {
        KanbanService.addKanbanToMyDashboard();
    }
}
