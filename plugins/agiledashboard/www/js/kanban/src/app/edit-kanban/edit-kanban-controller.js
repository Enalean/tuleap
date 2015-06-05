angular
    .module('kanban')
    .controller('EditKanbanCtrl', EditKanbanCtrl);

EditKanbanCtrl.$inject = ['$scope', '$window', '$modalInstance', 'KanbanService', 'kanban', 'SharedPropertiesService', 'gettextCatalog'];

function EditKanbanCtrl($scope, $window, $modalInstance, KanbanService, kanban, SharedPropertiesService, gettextCatalog) {
    _.extend($scope, {
        kanban            : kanban,
        saving            : false,
        cancel            : cancel,
        deleting          : false,
        processing        : processing,
        deleteKanban      : deleteKanban,
        saveModifications : saveModifications
    });

    function saveModifications() {
        $scope.saving = true;
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
        $scope.deleting = true;

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
        return $scope.deleting || $scope.saving;
    }
}