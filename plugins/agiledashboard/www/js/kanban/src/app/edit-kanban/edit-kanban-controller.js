angular
    .module('kanban')
    .controller('EditKanbanCtrl', EditKanbanCtrl);

EditKanbanCtrl.$inject = [
    '$scope',
    '$window',
    '$modalInstance',
    'KanbanService',
    'kanban',
    'augmentColumn',
    'updateKanbanName',
    'SharedPropertiesService',
    'gettextCatalog'
];

function EditKanbanCtrl($scope,
    $window,
    $modalInstance,
    KanbanService,
    kanban,
    augmentColumn,
    updateKanbanName,
    SharedPropertiesService,
    gettextCatalog
) {
    var self = this;

    _.extend(self, {
        kanban                   : kanban,
        saving                   : false,
        deleting                 : false,
        confirm_delete           : false,
        saving_new_column        : false,
        saving_column            : false,
        cancel                   : cancel,
        adding_column            : false,
        new_column_label         : '',
        reorderColumnsTreeOptions: {
            dropped: columnDropped
        },
        processing          : processing,
        deleteKanban        : deleteKanban,
        saveModifications   : saveModifications,
        addColumn           : addColumn,
        cancelAddColumn     : cancelAddColumn,
        removeColumn        : removeColumn,
        turnColumnToEditMode: turnColumnToEditMode,
        cancelEditColumn    : cancelEditColumn,
        editColumn          : editColumn
    });

    initModalValues();

    function initModalValues() {
        _.each(self.kanban.columns, function(column) {
            column.editing = false;
        });
    }

    function saveModifications() {
        self.saving = true;
        KanbanService.updateKanbanLabel(kanban.id, kanban.label).then(function () {
            self.saving = false;
            updateKanbanName(kanban);

        }, function (response) {
            $modalInstance.dismiss(response);
        });
    }

    function cancel() {
        $modalInstance.dismiss('cancel');
    }

    function deleteKanban() {
        if (self.confirm_delete) {
            self.deleting = true;

            KanbanService.deleteKanban(kanban.id).then(function () {
                var message = gettextCatalog.getString(
                    'Kanban {{ label }} successfuly deleted',
                    { label: kanban.label }
                );
                $window.sessionStorage.setItem('tuleap_feedback', message);
                $window.location.href = '/plugins/agiledashboard/?group_id=' + SharedPropertiesService.getProjectId();
            }, function (response) {
                $modalInstance.dismiss(response);
            });

        } else {
            self.confirm_delete = true;
        }
    }

    function processing() {
        return self.deleting || self.saving || self.saving_new_column || self.saving_column;
    }

    function cancelAddColumn() {
        self.new_column_label = '';
        self.adding_column    = false;
    }

    function addColumn() {
        if (self.adding_column) {
            self.saving_new_column = true;

            KanbanService.addColumn(kanban.id, self.new_column_label).then(function(column_representation) {
                var new_column = column_representation.data;

                augmentColumn(new_column);
                new_column.is_defered    = false;
                new_column.loading_items = false;

                kanban.columns.push(new_column);

                self.adding_column     = false;
                self.saving_new_column = false;
                self.new_column_label  = '';

            }, function (response) {
                $modalInstance.dismiss(response);
            });

        } else {
            self.adding_column    = true;
            self.new_column_label = '';
        }
    }

    function columnDropped(event) {
        var sorted_columns_ids = [];

        _.forEach(kanban.columns, function(column) {
            sorted_columns_ids.push(column.id);
        });

        KanbanService.reorderColumns(kanban.id, sorted_columns_ids).then(function() {
            // nothing to do
        }, function(response) {
            $modalInstance.dismiss(response);
        });
    }

    function editColumn(column) {
        self.saving_column = true;

        KanbanService.editColumn(kanban.id, column).then(function(data) {
            self.saving_column    = false;
            column.editing        = false;
            column.original_label = column.label;

        }, function (response) {
            $modalInstance.dismiss(response);
        });
    }

    function turnColumnToEditMode(column) {
        column.editing = true;
    }

    function cancelEditColumn(column) {
        self.saving_column = false;
        column.editing     = false;
        column.label       = column.original_label;
    }

    function removeColumn(column_id) {
        KanbanService.removeColumn(kanban.id, column_id).then(function() {
            _.remove(kanban.columns, function(column) {
                return column.id === column_id;
            });

        }, function(response) {
            $modalInstance.dismiss(response);
        });
    }
}
