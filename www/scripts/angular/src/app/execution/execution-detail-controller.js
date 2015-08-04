angular
    .module('execution')
    .controller('ExecutionDetailCtrl', ExecutionDetailCtrl);

ExecutionDetailCtrl.$inject = [
    '$scope',
    '$state',
    '$sce',
    '$rootScope',
    'gettextCatalog',
    'ExecutionService',
    'SharedPropertiesService',
    'SocketService',
    'ArtifactLinksGraphService',
    'ArtifactLinksGraphModalLoading'
];

function ExecutionDetailCtrl(
    $scope,
    $state,
    $sce,
    $rootScope,
    gettextCatalog,
    ExecutionService,
    SharedPropertiesService,
    SocketService,
    ArtifactLinksGraphService,
    ArtifactLinksGraphModalLoading
) {
    var execution_id = +$state.params.execid,
        campaign_id  = +$state.params.id;

    ExecutionService.loadExecutions(campaign_id);
    if (isCurrentExecutionLoaded()) {
        retrieveCurrentExecution();
    } else {
        waitForExecutionToBeLoaded();
    }

    $scope.pass                               = pass;
    $scope.fail                               = fail;
    $scope.block                              = block;
    $scope.sanitizeHtml                       = sanitizeHtml;
    $scope.getStatusLabel                     = getStatusLabel;
    $scope.showArtifactLinksGraphModal        = showArtifactLinksGraphModal;
    $scope.artifact_links_graph_modal_loading = ArtifactLinksGraphModalLoading.loading;

    viewTestExecution(execution_id, SharedPropertiesService.getCurrentUser());

    $scope.$on('$destroy', function iVeBeenDismissed() {
        viewTestExecution(execution_id, null);
    });

    function showArtifactLinksGraphModal(execution_id) {
        ArtifactLinksGraphService.showGraph(execution_id);
    }

    function viewTestExecution(execution_id, user) {
        SocketService.viewTestExecution({
            id: execution_id,
            user: user
        });
    }

    function waitForExecutionToBeLoaded() {
        var unbind = $rootScope.$on('bunchOfExecutionsLoaded', function () {
            if (isCurrentExecutionLoaded()) {
                retrieveCurrentExecution();
            }
        });
        $scope.$on('$destroy', unbind);
    }

    function retrieveCurrentExecution() {
        $scope.execution         = ExecutionService.executions[execution_id];
        $scope.execution.results = '';
        $scope.execution.saving  = false;
    }

    function isCurrentExecutionLoaded() {
        return typeof ExecutionService.executions[execution_id] !== 'undefined';
    }

    function sanitizeHtml(html) {
        if (html) {
            return $sce.trustAsHtml(html);
        }

        return null;
    }

    function pass(execution) {
        setNewStatus(execution, "passed");
    }

    function fail(execution) {
        setNewStatus(execution, "failed");
    }

    function block(execution) {
        setNewStatus(execution, "blocked");
    }

    function setNewStatus(execution, new_status) {
        var execution_to_save = angular.copy(execution);

        execution.saving               = true;
        execution.error                = null;
        execution_to_save.error        = null;
        execution_to_save.status       = new_status;
        execution_to_save.submitted_by = SharedPropertiesService.getCurrentUser();

        SocketService.updateTestExecution(execution_to_save);
    }

    function getStatusLabel(status) {
        var labels = {
            passed: 'Passed',
            failed: 'Failed',
            blocked: 'Blocked',
            notrun: 'Not Run'
        };

        return labels[status];
    }
}