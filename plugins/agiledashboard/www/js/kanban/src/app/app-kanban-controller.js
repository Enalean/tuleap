angular
    .module('kanban')
    .controller('KanbanCtrl', KanbanCtrl);

KanbanCtrl.$inject = [
    '$window',
    '$scope',
    '$modal',
    '$sce',
    '$q',
    'gettextCatalog',
    'amCalendarFilter',
    'SharedPropertiesService',
    'KanbanService',
    'KanbanItemRestService',
    'NewTuleapArtifactModalService',
    'UserPreferencesService',
    'SocketFactory',
    'KanbanColumnService',
    'ColumnCollectionService',
    'DroppedService',
    'KanbanFilterValue'
];

function KanbanCtrl(
    $window,
    $scope,
    $modal,
    $sce,
    $q,
    gettextCatalog,
    amCalendarFilter,
    SharedPropertiesService,
    KanbanService,
    KanbanItemRestService,
    NewTuleapArtifactModalService,
    UserPreferencesService,
    SocketFactory,
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

    self.label = kanban.label;
    self.board = {
        columns: kanban.columns
    };
    self.backlog = _.extend(kanban.backlog, {
        id                     : 'backlog',
        content                : [],
        nb_items_at_kanban_init: 0,
        filtered_content       : [],
        loading_items          : true,
        fully_loaded           : false,
        resize_left            : '',
        resize_top             : '',
        resize_width           : '',
        is_small_width         : false
    });
    self.archive = _.extend(kanban.archive, {
        id                     : 'archive',
        content                : [],
        nb_items_at_kanban_init: 0,
        filtered_content       : [],
        loading_items          : true,
        fully_loaded           : false,
        resize_left            : '',
        resize_top             : '',
        resize_width           : '',
        is_small_width         : false
    });

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

    function init() {
        initViewMode();
        loadColumns();
        loadBacklog(limit, offset);
        loadArchive(limit, offset);
        listenNodeJSServer();
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
    }

    function defineComparedTo(item_list, index) {
        return DroppedService.getComparedTo(item_list, index);
    }

    function emptyArray(array) {
        array.length = 0;
    }

    function reload(response) {
        $modal.open({
            keyboard: false,
            backdrop: 'static',
            templateUrl: 'error/error.tpl.html',
            controller: ErrorCtrl,
            controllerAs: 'modal',
            resolve: {
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
        $modal.open({
            backdrop: true,
            templateUrl: 'edit-kanban/edit-kanban.tpl.html',
            controller: 'EditKanbanCtrl as edit_modal',
            resolve: {
                kanban: function() {
                    return kanban;
                },
                addColumnToKanban: function() {
                    return addColumn;
                },
                removeColumnToKanban: function() {
                    return removeColumn;
                },
                updateKanbanName: function() {
                    return updateKanbanName;
                },
                deleteThisKanban: function() {
                    return deleteKanban;
                }
            }
        }).result.catch(
            reloadIfSomethingIsWrong
        );
    }

    function openReportModal() {
        $modal.open({
            backdrop   : true,
            templateUrl: 'reports-modal/reports-modal.tpl.html',
            controller : 'ReportsModalController as reports_modal',
            windowClass: 'reports-modal'
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

    function updateKanbanName(label) {
        kanban.label = label;
        self.label   = kanban.label;
    }

    function loadColumns() {
        kanban.columns.forEach(function (column) {
            augmentColumn(column);

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

    function augmentColumn(column) {
        column.content                 = [];
        column.filtered_content        = [];
        column.loading_items           = true;
        column.nb_items_at_kanban_init = 0;
        column.fully_loaded            = false;
        column.resize_left             = '';
        column.resize_top              = '';
        column.resize_width            = '';
        column.wip_in_edit             = false;
        column.limit_input             = column.limit;
        column.saving_wip              = false;
        column.is_small_width          = false;
        column.is_defered              = !column.is_open;
        column.original_label          = column.label;
    }

    function loadColumnContent(column, limit, offset) {
        column.loading_items = true;

        return KanbanService.getItems(kanban.id, column.id, limit, offset).then(function (data) {
            column.content = column.content.concat(data.results);

            if (column.is_open) {
                filterColumnCards(column);
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

        timeinfo += getTimeInfoEntry(item.timeinfo.kanban, gettextCatalog.getString('Kanban:'));
        timeinfo += getTimeInfoEntry(item.timeinfo[column.id], gettextCatalog.getString('Column:'));

        return $sce.trustAsHtml(timeinfo);
    }

    function getTimeInfoInArchive(item) {
        var timeinfo = '';

        if (!item.timeinfo) {
            return;
        }

        timeinfo += getTimeInfoEntry(item.timeinfo.kanban, gettextCatalog.getString('Kanban:'));
        timeinfo += getTimeInfoEntry(item.timeinfo.archive, gettextCatalog.getString('Archive:'));

        return $sce.trustAsHtml(timeinfo);
    }

    function getTimeInfoEntry(entry_date, label) {
        var timeinfo = '';

        if (entry_date) {
            timeinfo += '<p><span><i class="icon-signin"></i> ' + label + '</span>';
            timeinfo += ' <strong>' + amCalendarFilter(entry_date) + '</strong></p>';
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
        updateItem(item, item_updated);
    }

    function updateItem(item, item_updated) {
        var updated_item;

        updated_item = _.pick(item_updated, function (value, key) {
            return _.contains([
                'color',
                'item_name',
                'label'
            ], key);
        });

        _.extend(item, updated_item);

        if (item_updated.in_column === 'backlog') {
            updated_item = _.pick(item_updated, function (value, key) {
                return _.contains([
                    'card_fields',
                    'id',
                    'in_column'
                ], key);
            });
        } else {
            updated_item = _.pick(item_updated, function (value, key) {
                return _.contains([
                    'card_fields',
                    'id',
                    'in_column',
                    'timeinfo'
                ], key);
            });
        }

        _.extend(item, updated_item);
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
            compared_to
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

    function getComparedToBeLastItemOfColumn(column) {
        return DroppedService.getComparedToBeLastItemOfColumn(column);
    }

    function getColumn(id) {
        return ColumnCollectionService.getColumn(id);
    }

    function findItemInColumnById(item_id) {
        var item;
        self.board.columns.forEach(function (column) {
            var item_found = _.find(column.content, function (item) {
                return item.id === item_id;
            });

            if (item_found) {
                item = item_found;
                return;
            }
        });

        if (!item) {
            item = _.find(self.backlog.content, function (item) {
                return item.id === item_id;
            });
        }

        if (!item) {
            item = _.find(self.archive.content, function (item) {
                return item.id === item_id;
            });
        }

        return item;
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

    function addColumn(new_column) {
        augmentColumn(new_column);
        new_column.is_defered    = false;
        new_column.loading_items = false;

        kanban.columns.push(new_column);
    }

    function removeColumn(column_id) {
        var column_to_remove = getColumn(column_id);

        if(column_to_remove) {
            _.remove(kanban.columns, function(column) {
                return column.id === column_to_remove.id;
            });
        }
    }

    function deleteKanban() {
        var message = gettextCatalog.getString(
            'Kanban {{ label }} successfuly deleted',
            { label: kanban.label }
        );
        $window.sessionStorage.setItem('tuleap_feedback', message);
        $window.location.href = '/plugins/agiledashboard/?group_id=' + SharedPropertiesService.getProjectId();
    }

    function findAndMoveItem(id, destination_column_id, compared_to) {
        var promised_item = findItemInColumnById(id);

        if (! promised_item) {
            promised_item = KanbanItemRestService.getItem(id).then(function(item) {
                item.is_collapsed = SharedPropertiesService.doesUserPrefersCompactCards();
                return item;
            });
        }

        $q.when(promised_item).then(function(item) {
            if (! item) {
                return;
            }

            var source_column      = getColumn(item.in_column),
                destination_column = getColumn(destination_column_id);

            item.updating = false;

            KanbanColumnService.moveItem(
                item,
                source_column,
                destination_column,
                compared_to
            );

            filterColumnCards(destination_column);
        });
    }

    function listenNodeJSServer() {
        if (!_.isEmpty(SocketFactory)) {
            SocketFactory.then(function (data) {
                SocketFactory = data;
                listenKanbanItemCreate(SocketFactory);
                listenKanbanItemMove(SocketFactory);
                listenKanbanItemEdit(SocketFactory);
                listenKanbanColumnCreate(SocketFactory);
                listenKanbanColumnMove(SocketFactory);
                listenKanbanColumnEdit(SocketFactory);
                listenKanbanColumnDelete(SocketFactory);
                listenKanban(SocketFactory);
            });
        }
    }

    function listenKanbanItemCreate(SocketFactory) {
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

            var column      = getColumn(data.artifact.in_column),
                compared_to = getComparedToBeLastItemOfColumn(column);

            KanbanColumnService.addItem(
                data.artifact,
                column,
                compared_to
            );
        });
    }

    function listenKanbanItemMove(SocketFactory) {
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
                findAndMoveItem(id, data.in_column, compared_to);
            });
        });
    }

    function listenKanbanItemEdit(SocketFactory) {
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
            var item               = findItemInColumnById(data.artifact.id),
                destination_column = getColumn(data.artifact.in_column),
                compared_to        = defineComparedTo(destination_column.content, data.index);

            if (item) {
                _.extend(data.artifact, {
                    updating    : false,
                    is_collapsed: item.is_collapsed
                });

                var source_column = getColumn(item.in_column);

                KanbanColumnService.moveItem(
                    item,
                    source_column,
                    destination_column,
                    compared_to
                );
                updateItem(item, data.artifact);
            } else {
                _.extend(data.artifact, {
                    updating    : false,
                    is_collapsed: SharedPropertiesService.doesUserPrefersCompactCards()
                });

                data.artifact.timeinfo = {};

                KanbanColumnService.addItem(
                    data.artifact,
                    destination_column,
                    compared_to
                );
            }

            filterColumnCards(destination_column);
        });
    }

    function listenKanbanColumnCreate(SocketFactory) {
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
            addColumn(data);
        });
    }

    function listenKanbanColumnMove(SocketFactory) {
        /**
         * Data received looks like:
         * [15333, 15334, 15335, 15338]
         */
        SocketFactory.on('kanban_column:move', function (data) {
            var sorted_columns = [];

            _.forEach(data, function(column_id) {
                var column = getColumn(column_id);
                sorted_columns.push(column);
            });

            kanban.columns     = sorted_columns;
            self.board.columns = sorted_columns;
        });
    }

    function listenKanbanColumnEdit(SocketFactory) {
        /**
         * Data received looks like:
         * {
         *      id: 15343,
         *      label: "test",
         *      wip_limit: 0
         * }
         */
        SocketFactory.on('kanban_column:edit', function (data) {
            var column = getColumn(data.id);

            if(column) {
                column.label     = data.label;
                column.limit     = data.wip_limit;
                column.wip_limit = data.wip_limit;
            }
        });
    }

    function listenKanbanColumnDelete(SocketFactory) {
        /**
         * Data received looks like: 15233
         */
        SocketFactory.on('kanban_column:delete', function (data) {
            removeColumn(data);
        });
    }

    function listenKanban(SocketFactory) {
        /**
         * Data received looks like: "New Kanban Name"
         */
        SocketFactory.on('kanban:edit', function (data) {
            updateKanbanName(data);
        });

        /**
         * No data received
         */
        SocketFactory.on('kanban:delete', function () {
            deleteKanban();
        });
    }
}
