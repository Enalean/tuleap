angular
    .module('kanban')
    .controller('EditKanbanCtrl', EditKanbanCtrl);

EditKanbanCtrl.$inject = ['$scope', '$modalInstance', 'KanbanService', 'kanban'];

function EditKanbanCtrl($scope, $modalInstance, KanbanService, kanban) {
    $scope.kanban            = kanban;
    $scope.saving            = false;
    $scope.cancel            = cancel;
    $scope.saveModifications = saveModifications;

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
}