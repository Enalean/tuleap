import { element } from 'angular';
import { map } from 'lodash';

export default EditKanbanCtrl;

EditKanbanCtrl.$inject = [
    '$scope',
    'KanbanService',
    'ColumnCollectionService',
    'SharedPropertiesService',
    'RestErrorService'
];

function EditKanbanCtrl(
    $scope,
    KanbanService,
    ColumnCollectionService,
    SharedPropertiesService,
    RestErrorService
) {
    var self = this;
    self.kanban             = SharedPropertiesService.getKanban();
    self.saving             = false;
    self.deleting           = false;
    self.confirm_delete     = false;
    self.saving_new_column  = false;
    self.saving_column      = false;
    self.adding_column      = false;
    self.deleting_column    = false;
    self.new_column_label   = '';
    self.title_tracker_link = "<a class='edit-kanban-title-tracker-link' href='/plugins/tracker/?tracker=" + self.kanban.tracker.id + "'>" + self.kanban.tracker.label + "</a>";
    self.info_tracker_link  = "<a href='/plugins/tracker/?tracker=" + self.kanban.tracker.id + "'>" + self.kanban.tracker.label + "</a>";

    self.initModalValues             = initModalValues;
    self.initDragular                = initDragular;
    self.dragularOptionsForEditModal = dragularOptionsForEditModal;
    self.processing                  = processing;
    self.deleteKanban                = deleteKanban;
    self.cancelDeleteKanban          = cancelDeleteKanban;
    self.saveModifications           = saveModifications;
    self.addColumn                   = addColumn;
    self.cancelAddColumn             = cancelAddColumn;
    self.removeColumn                = removeColumn;
    self.cancelRemoveColumn          = cancelRemoveColumn;
    self.turnColumnToEditMode        = turnColumnToEditMode;
    self.cancelEditColumn            = cancelEditColumn;
    self.editColumn                  = editColumn;
    self.columnsCanBeManaged         = columnsCanBeManaged;

    self.initModalValues();
    self.initDragular();

    function updateKanbanName(label) {
        KanbanService.updateKanbanName(label);
    }

    function initModalValues() {
        self.kanban.columns.forEach(function (column) {
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
            element(handle_element)
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

        var sorted_columns_ids = map(self.kanban.columns, 'id');

        KanbanService.reorderColumns(self.kanban.id, sorted_columns_ids)
            .catch(function(response) {
                self.modal_instance.hide();
                RestErrorService.reload(response);
            });
    }

    function saveModifications() {
        self.saving = true;
        KanbanService.updateKanbanLabel(self.kanban.id, self.kanban.label).then(function () {
            self.saving = false;
            updateKanbanName(self.kanban.label);
        }, function (response) {
            self.modal_instance.hide();
            RestErrorService.reload(response);
        });
    }

    function deleteKanban() {
        if (self.confirm_delete) {
            self.deleting = true;

            KanbanService.deleteKanban(self.kanban.id)
                .then(function (response) {
                    self.modal_instance.hide();
                    RestErrorService.reload(response);
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

            KanbanService.addColumn(self.kanban.id, self.new_column_label).then(function(column_representation) {
                ColumnCollectionService.addColumn(column_representation.data);

                self.adding_column     = false;
                self.saving_new_column = false;
                self.new_column_label  = '';
            }, function (response) {
                self.modal_instance.hide();
                RestErrorService.reload(response);
            });
        } else {
            self.adding_column    = true;
            self.new_column_label = '';
        }
    }

    function editColumn(column) {
        self.saving_column = true;

        KanbanService.editColumn(self.kanban.id, column).then(function() {
            self.saving_column    = false;
            column.editing        = false;
            column.original_label = column.label;
        }, function (response) {
            self.modal_instance.hide();
            RestErrorService.reload(response);
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
            self.deleting_column = true;
            KanbanService.removeColumn(self.kanban.id, column_to_remove.id).then(function() {
                self.deleting_column = false;
                ColumnCollectionService.removeColumn(column_to_remove.id);
            }, function(response) {
                self.modal_instance.hide();
                RestErrorService.reload(response);
            });
        } else {
            column_to_remove.confirm_delete = true;
        }
    }

    function cancelRemoveColumn(column_to_remove) {
        column_to_remove.confirm_delete = false;
    }

    function columnsCanBeManaged() {
        return self.kanban.user_can_reorder_columns && self.kanban.user_can_add_columns;
    }
}
