(function () {
    angular
        .module('kanban')
        .controller('KanbanCtrl', KanbanCtrl);

    KanbanCtrl.$inject = [
        '$modal',
        '$sce',
        'gettextCatalog',
        'amCalendarFilter',
        'SharedPropertiesService',
        'KanbanService',
        'KanbanItemService',
        'CardFieldsService'
    ];

    function KanbanCtrl(
        $modal,
        $sce,
        gettextCatalog,
        amCalendarFilter,
        SharedPropertiesService,
        KanbanService,
        KanbanItemService,
        CardFieldsService
    ) {
        var self   = this,
            limit  = 50,
            offset = 0,
            kanban = SharedPropertiesService.getKanban();

        self.name      = kanban.name;
        self.board = {
            columns: []
        };
        self.backlog = {
            content: [],
            label: 'Backlog',
            is_open: false,
            loading_items: true,
            resize_left: '',
            resize_top: '',
            resize_width: '',
            is_small_width: false
        };
        self.archive = {
            content: [],
            label: 'Closed',
            is_open: false,
            loading_items: true,
            resize_left: '',
            resize_top: '',
            resize_width: '',
            is_small_width: false
        };

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

        loadColumns();
        loadBacklog(limit, offset);
        loadArchive(limit, offset);

        self.treeOptions = {
            dragStart: dragStart,
            dropped  : dropped
        };

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

            function updateTimeInfo(column_id, dropped_item) {
                dropped_item.timeinfo[column_id] = new Date();
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

        function loadColumns() {
            KanbanService.getKanban(kanban.id).then(function (kanban) {
                kanban.columns.forEach(function (column) {
                    column.content        = [];
                    column.loading_items  = true;
                    column.resize_left    = '';
                    column.resize_top     = '';
                    column.resize_width   = '';
                    column.wip_in_edit    = false;
                    column.limit_input    = column.limit;
                    column.saving_wip     = false;
                    column.is_small_width = false;
                    loadColumnContent(column, limit, offset);
                });
                self.board.columns = kanban.columns;
            });
        }

        function loadColumnContent(column, limit, offset) {
            return KanbanService.getItems(kanban.id, column.id, limit, offset).then(function(data) {
                column.content = column.content.concat(data.results);

                if (offset + limit < data.total) {
                    loadColumnContent(column, limit, offset + limit);
                } else {
                    column.loading_items = false;
                }
            });
        }

        function loadBacklog(limit, offset) {
            return KanbanService.getBacklog(kanban.id, limit, offset).then(function(data) {
                self.backlog.content = self.backlog.content.concat(data.results);

                if (offset + limit < data.total) {
                    loadBacklog(limit, offset + limit);
                } else {
                    self.backlog.loading_items = false;
                }
            });
        }

        function loadArchive(limit, offset) {
            return KanbanService.getArchive(kanban.id, limit, offset).then(function(data) {
                self.archive.content = self.archive.content.concat(data.results);

                if (offset + limit < data.total) {
                    loadArchive(limit, offset + limit);
                } else {
                    self.archive.loading_items = false;
                }
            });
        }

        function isColumnWipReached(column) {
            return (column.limit && column.limit < column.content.length);
        }

        function setWipLimitForColumn(column) {
            column.saving_wip = true;
            return KanbanService.setWipLimitForColumn(column.id, kanban.id, column.limit_input).then(function(data) {
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
                label: label,
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
                label: label,
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
    }
})();
