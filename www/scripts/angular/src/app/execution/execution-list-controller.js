angular
    .module('execution')
    .controller('ExecutionListCtrl', ExecutionListCtrl);

ExecutionListCtrl.$inject = [
    '$scope',
    '$state',
    '$filter',
    'ExecutionService',
    'CampaignService',
    'SocketService',
    'SharedPropertiesService'
];

function ExecutionListCtrl(
    $scope,
    $state,
    $filter,
    ExecutionService,
    CampaignService,
    SocketService,
    SharedPropertiesService
) {
    var campaign_id = parseInt($state.params.id, 10),
        execution_id = parseInt($state.params.execid, 10);

    SharedPropertiesService.setCampaignId(campaign_id);

    ExecutionService.loadExecutions(campaign_id).then(function() {
        ExecutionService.getGlobalPositions();
        ExecutionService.viewTestExecution(execution_id);
    });

    $scope.campaign             = CampaignService.getCampaign(campaign_id);
    $scope.categories           = ExecutionService.executions_by_categories_by_campaigns[campaign_id];
    $scope.environments         = [];
    $scope.search               = '';
    $scope.selected_environment = null;
    $scope.loading              = loading;
    $scope.status               = {
        passed:  false,
        failed:  false,
        blocked: false,
        notrun:  false
    };
    $scope.canCategoryBeDisplayed = canCategoryBeDisplayed;

    $scope.viewTestExecution = function(current_execution) {
        if (_.has(ExecutionService.executions, execution_id)) {
            var old_execution = ExecutionService.executions[execution_id];
            ExecutionService.removeViewTestExecution(old_execution.id);
        }
        ExecutionService.viewTestExecution(current_execution.id);
        execution_id = current_execution.id;
    };

    $scope.$on('$destroy', function() {
        ExecutionService.removeViewTestExecution(execution_id);
    });

    getEnvironments(campaign_id, 50, 0);

    ExecutionService.updateCampaign($scope.campaign);

    SocketService.listenNodeJSServer().then(function() {
        SocketService.listenToExecutionViewed();
        SocketService.listenToExecutionUpdated();
    });

    function getEnvironments(campaign_id, limit, offset) {
        CampaignService.getEnvironments(campaign_id, limit, offset).then(function(data) {
            $scope.environments = $scope.environments.concat(data.results);

            if ($scope.environments.length < data.total) {
                return getEnvironments(campaign_id, limit, offset + limit);
            }
        });
    }

    function loading() {
        return ExecutionService.loading[campaign_id] === true;
    }

    function canCategoryBeDisplayed(category) {
        return $filter('ExecutionListFilter')(
            category.executions,
            $scope.search,
            $scope.status,
            $scope.selected_environment
        ).length > 0;
    }
}