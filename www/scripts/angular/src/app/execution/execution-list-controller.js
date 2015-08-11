(function () {
    angular
        .module('execution')
        .controller('ExecutionListCtrl', ExecutionListCtrl);

    ExecutionListCtrl.$inject = [
        '$scope',
        '$state',
        '$filter',
        'ExecutionService',
        'CampaignService',
        'SocketService'
    ];

    function ExecutionListCtrl(
        $scope,
        $state,
        $filter,
        ExecutionService,
        CampaignService,
        SocketService
    ) {
        var campaign_id = $state.params.id;

        ExecutionService.loadExecutions(campaign_id);

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

        SocketService.listenToExecutionUpdated($scope.campaign);
        SocketService.listenToExecutionViewed();

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
})();