angular
    .module('kanban')
    .controller('EditKanbanCtrl', EditKanbanCtrl);

EditKanbanCtrl.$inject = [
    '$scope',
    '$modalInstance',
    'KanbanService',
    'kanban',
    'addColumnToKanban',
    'removeColumnToKanban',
    'updateKanbanName',
    'deleteThisKanban'
];

function EditKanbanCtrl(
    $scope,
    $modalInstance,
    KanbanService,
    kanban,
    addColumnToKanban,
    removeColumnToKanban,
    updateKanbanName,
    deleteThisKanban
) {
    var self = this;

    _.extend(self, {
        kanban           : kanban,
        saving           : false,
        deleting         : false,
        confirm_delete   : false,
        saving_new_column: false,
        saving_column    : false,
        cancel           : cancel,
        adding_column    : false,
        new_column_label : '',

        initModalValues            : initModalValues,
        initDragular               : initDragular,
        dragularOptionsForEditModal: dragularOptionsForEditModal,
        processing                 : processing,
        deleteKanban               : deleteKanban,
        cancelDeleteKanban         : cancelDeleteKanban,
        saveModifications          : saveModifications,
        addColumn                  : addColumn,
        cancelAddColumn            : cancelAddColumn,
        removeColumn               : removeColumn,
        cancelRemoveColumn         : cancelRemoveColumn,
        turnColumnToEditMode       : turnColumnToEditMode,
        cancelEditColumn           : cancelEditColumn,
        editColumn                 : editColumn,
        columnsCanBeManaged        : columnsCanBeManaged
    });

    self.initModalValues();
    self.initDragular();

    function initModalValues() {
        _.each(self.kanban.columns, function(column) {
            column.editing        = false;
            column.confirm_delete = false;
        });
    }

    function initDragular() {
        $scope.$on('dragulardrop', dragularDrop);
    }

    function dragularOptionsForEditModal() {
        return {
            containersModel: self.kanban.columns,
            scope          : $scope,
            revertOnSpill  : true,
            nameSpace      : 'dragular-columns',
            moves          : isItemDraggable
        };
    }

    function isItemDraggable(element_to_drag, container, handle_element) {
        return (! ancestorCannotBeDragged(handle_element));
    }

    function ancestorCannotBeDragged(handle_element) {
        return (
            angular.element(handle_element)
                .parentsUntil('.column')
                .andSelf()
                .filter('[data-nodrag="true"]')
                .length > 0
        );
    }

    function dragularDrop(
        event
    ) {
        event.stopPropagation();

        var sorted_columns_ids = _.map(self.kanban.columns, 'id');

        KanbanService.reorderColumns(self.kanban.id, sorted_columns_ids)
        .catch(function(response) {
            $modalInstance.dismiss(response);
        });
    }

    function saveModifications() {
        self.saving = true;
        KanbanService.updateKanbanLabel(kanban.id, kanban.label).then(function () {
            self.saving = false;
            updateKanbanName(kanban.label);
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
                deleteThisKanban();
            }, function (response) {
                $modalInstance.dismiss(response);
            });
        } else {
            self.confirm_delete = true;
        }
    }

    function cancelDeleteKanban() {
        self.confirm_delete = false;
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
                addColumnToKanban(column_representation.data);

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

    function editColumn(column) {
        self.saving_column = true;

        KanbanService.editColumn(kanban.id, column).then(function() {
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

    function removeColumn(column_to_remove) {
        if (column_to_remove.confirm_delete) {
            KanbanService.removeColumn(kanban.id, column_to_remove.id).then(function() {
                removeColumnToKanban(column_to_remove.id);
            }, function(response) {
                $modalInstance.dismiss(response);
            });
        } else {
            column_to_remove.confirm_delete = true;
        }
    }

    function cancelRemoveColumn(column_to_remove) {
        column_to_remove.confirm_delete = false;
    }

    function columnsCanBeManaged() {
        return kanban.user_can_reorder_columns && kanban.user_can_add_columns;
    }
}
