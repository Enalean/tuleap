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
    'SharedPropertiesService',
    'ExecutionRestService'
];

function ExecutionListCtrl(
    $scope,
    $state,
    $filter,
    ExecutionService,
    CampaignService,
    SocketService,
    SharedPropertiesService,
    ExecutionRestService
) {
    var campaign_id,
        execution_id;

    initialization();

    $scope.viewTestExecution = function(current_execution) {
        var old_execution,
            old_execution_id = '';

        if (_.has(ExecutionService.executions, execution_id)) {
            old_execution = ExecutionService.executions[execution_id];
        }

        if (! _.isEmpty(old_execution)) {
            if (current_execution.id !== old_execution.id) {
                old_execution_id = old_execution.id;
                updateViewTestExecution(current_execution.id, old_execution_id);
            }
        } else {
            updateViewTestExecution(current_execution.id, old_execution_id);
        }
    };

    $scope.$on('$destroy', function() {
        if (execution_id) {
            ExecutionRestService.leaveTestExecution(execution_id);
            ExecutionService.removeViewTestExecution(execution_id, SharedPropertiesService.getCurrentUser());
        }
    });

    $scope.$on('controller-reload', function() {
        initialization();
    });

    SocketService.listenNodeJSServer().then(function() {
        SocketService.listenToExecutionViewed();
        SocketService.listenToExecutionUpdated();
        SocketService.listenToExecutionLeft();
    });

    function initialization() {
        campaign_id = parseInt($state.params.id, 10);
        execution_id = parseInt($state.params.execid, 10);

        SharedPropertiesService.setCampaignId(campaign_id);

        ExecutionService.loadExecutions(campaign_id).then(function() {
            ExecutionService.removeAllViewTestExecution();
            ExecutionService.viewTestExecution(execution_id, SharedPropertiesService.getCurrentUser());
            ExecutionService.getGlobalPositions();
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

        getEnvironments(campaign_id, 50, 0);

        ExecutionService.updateCampaign($scope.campaign);
    }

    function updateViewTestExecution(current_execution_id, old_execution_id) {
        ExecutionRestService.changePresenceOnTestExecution(current_execution_id, old_execution_id);
        ExecutionService.removeViewTestExecution(old_execution_id, SharedPropertiesService.getCurrentUser());
        ExecutionService.viewTestExecution(current_execution_id, SharedPropertiesService.getCurrentUser());
        execution_id = current_execution_id;
    }

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