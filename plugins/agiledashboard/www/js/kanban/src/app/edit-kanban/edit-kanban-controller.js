angular
    .module('kanban')
    .controller('EditKanbanCtrl', EditKanbanCtrl);

EditKanbanCtrl.$inject = ['$scope', '$window', '$modalInstance', 'KanbanService', 'kanban', 'augmentColumn', 'SharedPropertiesService', 'gettextCatalog'];

function EditKanbanCtrl($scope, $window, $modalInstance, KanbanService, kanban, augmentColumn, SharedPropertiesService, gettextCatalog) {
    var self = this;

    _.extend(self, {
        kanban                   : kanban,
        saving                   : false,
        cancel                   : cancel,
        deleting                 : false,
        adding_column            : false,
        new_column_label         : '',
        reorderColumnsTreeOptions: {
            dropped: columnDropped
        },
        processing       : processing,
        deleteKanban     : deleteKanban,
        saveModifications: saveModifications,
        addColumn        : addColumn,
        cancelAddColumn  : cancelAddColumn
    });

    function saveModifications() {
        self.saving = true;
        KanbanService.updateKanbanLabel(kanban.id, kanban.label).then(function () {
            $modalInstance.close(kanban);
        }, function (response) {
            $modalInstance.dismiss(response);
        });
    }

    function cancel() {
        $modalInstance.dismiss('cancel');
    }

    function deleteKanban() {
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
    }

    function processing() {
        return self.deleting || self.saving;
    }

    function cancelAddColumn() {
        self.adding_column = false;
    }

    function addColumn() {
        if (self.adding_column) {
            KanbanService.addColumn(kanban.id, self.new_column_label).then(function(column_representation) {
                var new_column = column_representation.data;

                augmentColumn(new_column);
                new_column.is_defered    = false;
                new_column.loading_items = false;

                kanban.columns.push(new_column);

                self.adding_column    = false;
                self.new_column_label = '';

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
}
