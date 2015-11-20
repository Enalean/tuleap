(function () {
    angular
        .module('kanban')
        .controller('KanbanCtrl', KanbanCtrl);

    KanbanCtrl.$inject = [
        '$scope',
        '$filter',
        '$modal',
        '$sce',
        '$q',
        'gettextCatalog',
        'amCalendarFilter',
        'SharedPropertiesService',
        'KanbanService',
        'KanbanItemService',
        'CardFieldsService',
        'NewTuleapArtifactModalService',
        'TuleapArtifactModalLoading',
        'UserPreferencesService',
        'SocketFactory'
    ];

    function KanbanCtrl(
        $scope,
        $filter,
        $modal,
        $sce,
        $q,
        gettextCatalog,
        amCalendarFilter,
        SharedPropertiesService,
        KanbanService,
        KanbanItemService,
        CardFieldsService,
        NewTuleapArtifactModalService,
        TuleapArtifactModalLoading,
        UserPreferencesService,
        SocketFactory
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
            content: [],
            filtered_content: [],
            loading_items: true,
            resize_left: '',
            resize_top: '',
            resize_width: '',
            is_small_width: false
        });
        self.archive = _.extend(kanban.archive, {
            content: [],
            filtered_content: [],
            loading_items: true,
            resize_left: '',
            resize_top: '',
            resize_width: '',
            is_small_width: false
        });

        self.detailed_view_key            = 'detailed-view';
        self.compact_view_key             = 'compact-view';
        self.cardFieldIsSimpleValue       = CardFieldsService.cardFieldIsSimpleValue;
        self.cardFieldIsList              = CardFieldsService.cardFieldIsList;
        self.cardFieldIsText              = CardFieldsService.cardFieldIsText;
        self.cardFieldIsDate              = CardFieldsService.cardFieldIsDate;
        self.cardFieldIsFile              = CardFieldsService.cardFieldIsFile;
        self.cardFieldIsCross             = CardFieldsService.cardFieldIsCross;
        self.cardFieldIsPermissions       = CardFieldsService.cardFieldIsPermissions;
        self.cardFieldIsUser              = CardFieldsService.cardFieldIsUser;
        self.getCardFieldListValues       = CardFieldsService.getCardFieldListValues;
        self.getCardFieldTextValue        = CardFieldsService.getCardFieldTextValue;
        self.getCardFieldFileValue        = CardFieldsService.getCardFieldFileValue;
        self.getCardFieldCrossValue       = CardFieldsService.getCardFieldCrossValue;
        self.getCardFieldPermissionsValue = CardFieldsService.getCardFieldPermissionsValue;
        self.getCardFieldUserValue        = CardFieldsService.getCardFieldUserValue;
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
        self.filter_terms                 = '';
        self.treeFilter                   = filterCards;
        self.loading_modal                = TuleapArtifactModalLoading.loading;
        self.showEditModal                = showEditModal;
        self.moveItemAtTheEnd             = moveItemAtTheEnd;
        self.switchViewMode               = switchViewMode;

        initViewMode();
        loadColumns();
        loadBacklog(limit, offset);
        loadArchive(limit, offset);
        listenNodeJSServer();

        self.treeOptions = {
            dragStart: dragStart,
            dropped  : dropped
        };

        function initViewMode() {
            self.current_view_class = self.compact_view_key;

            if (SharedPropertiesService.getViewMode()) {
                self.current_view_class = SharedPropertiesService.getViewMode();
            }
        }

        function switchViewMode(view_mode) {
            self.current_view_class = view_mode;
            UserPreferencesService.setPreference(user_id, 'agiledashboard_kanban_item_view_mode_' + kanban.id, view_mode);
            $scope.$broadcast('rebuild:kustom-scroll');
        }

        function filterCards() {
            self.backlog.filtered_content = $filter('InPropertiesFilter')(self.backlog.content, self.filter_terms);
            self.archive.filtered_content = $filter('InPropertiesFilter')(self.archive.content, self.filter_terms);

            self.board.columns.forEach(function(column) {
                column.filtered_content = $filter('InPropertiesFilter')(column.content, self.filter_terms);
            });

            reflowKustomScrollBars();
        }

        function reflowKustomScrollBars() {
            $scope.$broadcast('rebuild:kustom-scroll');
        }

        function collapseColumn(column) {
            if (column.is_open) {
                KanbanService.collapseColumn(kanban.id, column.id);
                column.is_open = false;
            }
        }

        function expandColumn(column) {
            if (! column.is_open) {
                KanbanService.expandColumn(kanban.id, column.id);
                column.is_open = true;
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
                KanbanService.collapseBacklog(kanban.id);
                self.backlog.is_open = false;
            }
        }

        function expandBacklog() {
            if (! self.backlog.is_open) {
                KanbanService.expandBacklog(kanban.id);
                self.backlog.is_open = true;
            }
        }

        function toggleBacklog() {
            if (self.backlog.is_open) {
                collapseBacklog();
            } else {
                expandBacklog();
            }
        }

        function collapseArchive() {
            if (self.archive.is_open) {
                KanbanService.collapseArchive(kanban.id);
                self.archive.is_open = false;
            }
        }

        function expandArchive() {
            if (! self.archive.is_open) {
                KanbanService.expandArchive(kanban.id);
                self.archive.is_open = true;
            }
        }

        function toggleArchive() {
            if (self.archive.is_open) {
                collapseArchive();
            } else {
                expandArchive();
            }
        }

        function dragStart(event) {
            self.board.columns.forEach(function(column) {
                column.wip_in_edit = false;
            });
        }

        function dropped(event) {
            var dropped_item        = event.source.nodeScope.$modelValue,
                compared_to         = defineComparedTo(event.dest.nodesScope.$modelValue, event.dest.index),
                source_list_element = event.source.nodesScope.$element,
                dest_list_element   = event.dest.nodesScope.$element;

            if (dropped_item.in_column === 'backlog' && ! dest_list_element.hasClass('backlog')) {
                updateTimeInfo('kanban', dropped_item);
            }

            if (dest_list_element.hasClass('backlog')) {
                return droppedInBacklog(event, dropped_item, compared_to);
            } else if(dest_list_element.hasClass('archive')) {
                return droppedInArchive(event, dropped_item, compared_to);
            } else if (dest_list_element.hasClass('column')) {
                var column_id = dest_list_element.attr('data-column-id');
                return droppedInColumn(event, column_id, dropped_item, compared_to);
            }

            function droppedInBacklog(event, dropped_item, compared_to) {
                if (isDroppedInSameColumn(event) && compared_to) {
                    KanbanService
                        .reorderBacklog(kanban.id, dropped_item.id, compared_to)
                        .then(null, reload);
                } else {
                    KanbanService
                        .moveInBacklog(kanban.id, dropped_item.id, compared_to)
                        .then(null, reload);
                }
            }

            function droppedInArchive(event, dropped_item, compared_to) {
                if (isDroppedInSameColumn(event) && compared_to) {
                    KanbanService
                        .reorderArchive(kanban.id, dropped_item.id, compared_to)
                        .then(null, reload);
                } else {
                    KanbanService
                        .moveInArchive(kanban.id, dropped_item.id, compared_to)
                        .then(
                            function () {
                                updateTimeInfo('archive', dropped_item);
                            },
                            reload
                    );
                }
            }

            function droppedInColumn(event, column_id, dropped_item, compared_to) {
                if (isDroppedInSameColumn(event) && compared_to) {
                    KanbanService
                        .reorderColumn(kanban.id, column_id, dropped_item.id, compared_to)
                        .then(null, reload);
                } else {
                    KanbanService
                        .moveInColumn(kanban.id, column_id, dropped_item.id, compared_to)
                        .then(
                            function () {
                                updateTimeInfo(column_id, dropped_item);
                            },
                            reload
                    );
                }
            }

            function isDroppedInSameColumn(event) {
                return event.source.nodesScope.$id === event.dest.nodesScope.$id;
            }

            function defineComparedTo(item_list, index) {
                var compared_to = {};

                if (item_list.length === 1) {
                    return null;
                }

                if (index === 0) {
                    compared_to.direction = 'before';
                    compared_to.item_id   = item_list[index + 1].id;

                    return compared_to;
                }

                compared_to.direction = 'after';
                compared_to.item_id   = item_list[index - 1].id;

                return compared_to;
            }
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
                        var message = response.status +' '+ response.statusText;
                        if (response.data.error) {
                            message = response.data.error.code +' '+ response.data.error.message;
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
                backdrop:     true,
                templateUrl:  'edit-kanban/edit-kanban.tpl.html',
                controller:   'EditKanbanCtrl as edit_modal',
                resolve: {
                    kanban: function() {
                        return kanban;
                    },
                    augmentColumn: function() {
                        return augmentColumn;
                    },
                    updateKanbanName: function() {
                        return updateKanbanName;
                    }
                }
            }).result.then(
                updateKanbanName,
                reloadIfSomethingIsWrong
            );

            function updateKanbanName(new_kanban) {
                kanban.label = new_kanban.label;
                self.label   = kanban.label;
            }

            function reloadIfSomethingIsWrong(reason) {
                if (reason && reason.status) {
                    // the edit-kanban-controller dismissed the dialog
                    // due to an error in PATCH response and it's passed to us
                    // the failing request as a reason so that we can display
                    // the error details in the error modal.
                    // For more details, see https://angular-ui.github.io/bootstrap/#/modal
                    reload(reason);
                }
            }
        }

        function loadColumns() {
            kanban.columns.forEach(function (column) {
                augmentColumn(column);

                if (column.is_open) {
                    loadColumnContent(column, limit, offset);
                }
            });
        }

        function augmentColumn(column) {
            column.content          = [];
            column.filtered_content = column.content;
            column.loading_items    = true;
            column.resize_left      = '';
            column.resize_top       = '';
            column.resize_width     = '';
            column.wip_in_edit      = false;
            column.limit_input      = column.limit;
            column.saving_wip       = false;
            column.is_small_width   = false;
            column.is_defered       = ! column.is_open;
            column.original_label   = column.label;
        }

        function loadDeferedColumns() {
            if (kanban.columns.every(function (column){
                    return column.is_defered || ! column.loading_items;
                })) {
                kanban.columns.forEach(function (column) {
                    if (column.is_defered) {
                        column.is_defered = false;
                        loadColumnContent(column, limit, offset);
                    }
                });
            }
        }

        function loadColumnContent(column, limit, offset) {
            return KanbanService.getItems(kanban.id, column.id, limit, offset).then(function(data) {
                column.content          = column.content.concat(data.results);
                column.filtered_content = column.content;

                if (offset + limit < data.total) {
                    loadColumnContent(column, limit, offset + limit);
                } else {
                    column.loading_items = false;
                    loadDeferedColumns();
                }
            });
        }

        function loadBacklog(limit, offset) {
            return KanbanService.getBacklog(kanban.id, limit, offset).then(function(data) {
                self.backlog.content          = self.backlog.content.concat(data.results);
                self.backlog.filtered_content = self.backlog.content;

                if (offset + limit < data.total) {
                    loadBacklog(limit, offset + limit);
                } else {
                    self.backlog.loading_items = false;
                    loadDeferedColumns();
                }
            });
        }

        function loadArchive(limit, offset) {
            return KanbanService.getArchive(kanban.id, limit, offset).then(function(data) {
                self.archive.content          = self.archive.content.concat(data.results);
                self.archive.filtered_content = self.archive.content;

                if (offset + limit < data.total) {
                    loadArchive(limit, offset + limit);
                } else {
                    self.archive.loading_items = false;
                    loadDeferedColumns();
                }
            });
        }

        function isColumnWipReached(column) {
            return (column.limit && column.limit < column.content.length);
        }

        function setWipLimitForColumn(column) {
            column.saving_wip = true;
            return KanbanService.editColumn(kanban.id, column).then(function(data) {
                column.limit       = column.limit_input;
                column.wip_in_edit = false;
                column.saving_wip  = false;
            },
                reload
            );
        }

        function userIsAdmin() {
            return SharedPropertiesService.getUserIsAdmin();
        }

        function getTimeInfo(column, item) {
            var timeinfo = '';

            if (! column || ! item.timeinfo) {
                return;
            }

            timeinfo += getTimeInfoEntry(item.timeinfo.kanban, gettextCatalog.getString('Kanban:'));
            timeinfo += getTimeInfoEntry(item.timeinfo[column.id], gettextCatalog.getString('Column:'));

            return $sce.trustAsHtml(timeinfo);
        }

        function getTimeInfoInArchive(item) {
            var timeinfo = '';

            if (! item.timeinfo) {
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
                label: label,
                updating: true
            };

            self.backlog.content.push(item);

            KanbanItemService.createItemInBacklog(kanban.id, item.label).then(
                function (response) {
                    item.updating = false;
                    _.extend(item, response.data);
                },
                reload
            );
        }

        function createItemInPlace(label, column) {
            var item = {
                label: label,
                updating: true
            };

            column.content.push(item);

            KanbanItemService.createItem(kanban.id, column.id, item.label).then(
                function (response) {
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

                    return KanbanItemService.getItem(artifact_id).then(function(data) {
                        var updated_item = _.pick(data, function(value, key) {
                            return _.contains([
                                'card_fields',
                                'color',
                                'id',
                                'in_column',
                                'item_name',
                                'label',
                                'timeinfo'
                            ], key);
                        });

                        if (checkColumnChanged(item, updated_item)) {
                            self.moveItemAtTheEnd(item, updated_item.in_column);
                        } else {
                            item.updating = false;
                        }

                        _.extend(item, updated_item);
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

        function checkColumnChanged(item, updated_item) {
            var previous_column = getColumn(item.in_column),
                new_column      = getColumn(updated_item.in_column);
            return previous_column !== new_column;
        }

        /**
         * Move item in column at index. Removes the item from
         * its previous column.
         * @param  {Object} item      The kanban item to move
         * @param  {int}    column_id The destination column's id
         * @return {Promise}          A promise that will be resolved when the item has been moved.
         */
        function moveItemAtTheEnd(item, column_id) {
            var previous_column          = getColumn(item.in_column),
                new_column               = getColumn(column_id),
                previous_index_in_column = getItemIndex(item);
            item.updating                = true;

            var promise = moveItemInBackend(item, column_id).then(function() {
                item.updating  = false;
                // Update in the view
                item.in_column = column_id;
                previous_column.content.splice(previous_index_in_column, 1);
                new_column.content.push(item);
            }, reload);

            return promise;
        }

        function moveItemInBackend(item, column_id) {
            var promise,
                new_column  = getColumn(column_id),
                compared_to = getComparedToForLastItemOfColumn(new_column);
            if (column_id === 'archive') {
                promise = KanbanService
                    .moveInArchive(kanban.id, item.id, compared_to);
            } else if (column_id === 'backlog') {
                promise = KanbanService
                    .moveInBacklog(kanban.id, item.id, compared_to);
            } else if (column_id) {
                promise = KanbanService
                    .moveInColumn(kanban.id, column_id, item.id, compared_to);
            }

            return promise;
        }

        function getComparedToForLastItemOfColumn(column) {
            if (column.content.length === 0) {
                return null;
            }

            var last_item = column.content[column.content.length - 1];
            var compared_to = {
                direction: 'after',
                item_id: last_item.id
            };

            return compared_to;
        }

        function getColumn(id) {
            if (id === 'archive') {
                return self.archive;
            } else if (id === 'backlog') {
                return self.backlog;
            } else if (id) {
                return getBoardColumn(id);
            }

            return undefined;
        }

        function getBoardColumn(id) {
            return _.find(self.board.columns, function(column) {
                return column.id === id;
            });
        }

        function getItemIndex(item) {
            var column = getColumn(item.in_column),
                index  = _.indexOf(column.content, item);

            return index;
        }

        function getContentColumnByItemInColumn(data, item) {
            var content;
            if (data.in_column === 'archive') {
                content = self.archive.content;
            } else if (data.in_column === 'backlog') {
                content = self.backlog.content;
            } else {
                content = getColumn(item.in_column).content;
            }
            return content;
        }

        function findItemInColumnById(item_id) {
            var item;
            self.board.columns.forEach(function(column) {
                var item_found = _.find(column.content, function (item) {
                    return item.id === item_id;
                });

                if(item_found) {
                    item = item_found;
                    return;
                }
            });

            if (! item) {
                item = _.find(self.backlog.content, function (item) {
                    return item.id === item_id;
                });
            }

            if (! item) {
                item = _.find(self.archive.content, function (item) {
                    return item.id === item_id;
                });
            }

            return item;
        }

        function replaceCard(compared_to, direction, content, item) {
            var compared_to_index = _.findIndex(content, {
                id: compared_to
            });
            if (direction === 'after') {
                content.splice(compared_to_index + 1, 0, item);
            } else {
                content.splice(compared_to_index, 0, item);
            }
        }

        function checkServerNodeJSExist() {
            return SocketFactory.hasOwnProperty('on');
        }

        function updateTimeInfo(column_id, dropped_item) {
            dropped_item.timeinfo[column_id] = new Date();
        }

        function removeItemInColumn(item_id, column) {
            var column_remove = getColumn(column);
            _.remove(column_remove.content, {
                id: item_id
            });
        }

        function updateItemColumn(item, column) {
            item.in_column = column;
        }

        function listenNodeJSServer() {
            if (checkServerNodeJSExist()) {
                /**
                 * Data received looks like:
                 *  {
                 *      id: 79584,
                 *      item_name: 'kanbantask',
                 *      label: 'Documentation API',
                 *      color: 'inca_silver',
                 *      card_fields: [
                 *          {
                 *              field_id: 15261,
                 *              type: 'msb',
                 *              label: 'Assigned to',
                 *              values: [Object],
                 *              bind_value_ids: [Object]
                 *          }
                 *      ],
                 *      timeinfo: {
                 *                  kanban: null,
                 *                  archive: null
                 *                },
                 *      in_column: 'backlog'
                 *  }
                 *
                 */
                SocketFactory.on('kanban_item:create', function (data) {
                    _.extend(data, {
                        updating: false
                    });

                    var column = getColumn(data.in_column);
                    column.content.push(data);
                });

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
                SocketFactory.on('kanban_item:move', function (data) {
                    var ids = data.add ? data.add.ids : data.order.ids;
                    ids.forEach(function (id) {
                        var item = findItemInColumnById(id);

                        _.extend(item, {
                            updating: false
                        });

                        removeItemInColumn(item.id, item.in_column);

                        if(item.in_column !== data.in_column) {
                            if(item.in_column === 'backlog') {
                                updateTimeInfo('kanban', item);
                            }
                            updateTimeInfo(data.in_column, item);
                            updateItemColumn(item, data.in_column);
                        }

                        var content = getContentColumnByItemInColumn(data, item);
                        if(data.order) {
                            replaceCard(data.order.compared_to, data.order.direction, content, item);
                        } else {
                            content.push(item);
                        }
                    });
                });
            }
        }
    }
})();
