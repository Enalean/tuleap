angular
    .module('execution')
    .controller('ExecutionListCtrl', ExecutionListCtrl);

ExecutionListCtrl.$inject = [
    '$scope',
    '$state',
    '$filter',
    '$q',
    'ExecutionService',
    'CampaignService',
    'SocketService',
    'SharedPropertiesService',
    'ExecutionRestService',
    'NewTuleapArtifactModalService'
];

function ExecutionListCtrl(
    $scope,
    $state,
    $filter,
    $q,
    ExecutionService,
    CampaignService,
    SocketService,
    SharedPropertiesService,
    ExecutionRestService,
    NewTuleapArtifactModalService
) {
    var campaign_id,
        execution_id;

    _.extend($scope, {
        showAddTestModal           : showAddTestModal,
        checkActiveClassOnExecution: checkActiveClassOnExecution,
        viewTestExecution          : viewTestExecution,
        showPresencesModal         : showPresencesModal
    });

    function checkActiveClassOnExecution(execution) {
        return $state.includes('campaigns.executions.detail', { execid: execution.id, defid: execution.definition.id });
    }

    function viewTestExecution(current_execution) {
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
    }

    function showPresencesModal() {
        ExecutionService.showPresencesModal();
    }

    function showAddTestModal() {
        var callback = function(artifact_id) {
            var execution_tracker_id = SharedPropertiesService.getExecutionTrackerId();

            ExecutionRestService.postTestExecution(execution_tracker_id, artifact_id, 'notrun').then(function(execution) {
                return CampaignService.patchCampaign(campaign_id, [execution.id]);
            }).then(function(executions) {
                _.forEach(executions, ExecutionService.addTestExecutions);
            });
        };

        NewTuleapArtifactModalService.showCreation(SharedPropertiesService.getDefinitionTrackerId(), null, callback);
    }

    $scope.$on('$destroy', function() {
        var toolbar = angular.element('.toolbar');
        if (toolbar) {
            toolbar.removeClass('hide-toolbar');
        }

        if (execution_id) {
            ExecutionRestService.leaveTestExecution(execution_id);
            ExecutionService.removeViewTestExecution(execution_id, SharedPropertiesService.getCurrentUser());
        }

        ExecutionService.removeAllPresencesOnCampaign();
    });

    $scope.$on('execution-detail-destroy', function() {
        execution_id = '';
    });

    $scope.$on('controller-reload', function() {
        initialization();
    });

    SocketService.listenNodeJSServer().then(function() {
        SocketService.listenToUserScore();
        SocketService.listenTokenExpired();
        SocketService.listenToExecutionViewed();
        SocketService.listenToExecutionCreated();
        SocketService.listenToExecutionUpdated();
        SocketService.listenToExecutionLeft();
    });

    initialization();

    function initialization() {
        var toolbar = angular.element('.toolbar');
        if (toolbar) {
            toolbar.addClass('hide-toolbar');
        }

        campaign_id = parseInt($state.params.id, 10);
        execution_id = parseInt($state.params.execid, 10);

        SharedPropertiesService.setCampaignId(campaign_id);

        ExecutionService.loadExecutions(campaign_id).then(function() {
            ExecutionService.removeAllViewTestExecution();
            if (execution_id) {
                updateViewTestExecution(execution_id, '');
            }

            ExecutionService.executions_loaded = true;
            ExecutionService.displayPresencesForAllExecutions();
        });

        $scope.campaign             = CampaignService.getCampaign(campaign_id);
        $scope.categories           = ExecutionService.executions_by_categories_by_campaigns[campaign_id];
        $scope.search               = '';
        $scope.loading              = loading;
        $scope.status               = {
            passed:  false,
            failed:  false,
            blocked: false,
            notrun:  false
        };
        $scope.canCategoryBeDisplayed = canCategoryBeDisplayed;
        $scope.presences_on_campaign  = ExecutionService.presences_on_campaign;

        ExecutionService.updateCampaign($scope.campaign);
    }

    function updateViewTestExecution(current_execution_id, old_execution_id) {
        ExecutionService.addPresenceCampaign(SharedPropertiesService.getCurrentUser());

        ExecutionRestService.changePresenceOnTestExecution(current_execution_id, old_execution_id).then(function() {
            ExecutionService.removeViewTestExecution(old_execution_id, SharedPropertiesService.getCurrentUser());
            ExecutionService.viewTestExecution(current_execution_id, SharedPropertiesService.getCurrentUser());
            execution_id = current_execution_id;
        });
    }

    function loading() {
        return ExecutionService.loading[campaign_id] === true;
    }

    function canCategoryBeDisplayed(category) {
        return $filter('ExecutionListFilter')(
            category.executions,
            $scope.search,
            $scope.status
        ).length > 0;
    }
}
