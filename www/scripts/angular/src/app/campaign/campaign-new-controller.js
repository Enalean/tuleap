(function () {
    angular
        .module('campaign')
        .controller('CampaignNewCtrl', CampaignNewCtrl);

    CampaignNewCtrl.$inject = [
        '$scope',
        '$state',
        'gettextCatalog',
        'CampaignService',
        'EnvironmentService',
        'SharedPropertiesService'
    ];

    function CampaignNewCtrl(
        $scope,
        $state,
        gettextCatalog,
        CampaignService,
        EnvironmentService,
        SharedPropertiesService
    ) {
        var project_id = SharedPropertiesService.getProjectId();

        $scope.campaign = {
            project_id: project_id,
            environments: []
        };
        $scope.loading_environments = true;
        $scope.breadcrumb_label     = gettextCatalog.getString('Campaign creation');
        $scope.createCampaign       = createCampaign;

        getEnvironments(project_id, 50, 0);

        function createCampaign(campaign) {
            CampaignService
                .createCampaign(campaign)
                .then(function () {
                    $state.go('campaigns.list', {}, {reload: true});
            });
        }

        function getEnvironments(project_id, limit, offset) {
            EnvironmentService.getEnvironments(project_id, limit, offset).then(function(data) {
                data.results.forEach(addPossibleEnvironmentInCampaign);

                if ($scope.campaign.environments.length < data.total) {
                    getEnvironments(project_id, limit, offset + limit);
                } else {
                    $scope.loading_environments = false;
                }
            });
        }

        function addPossibleEnvironmentInCampaign(environment) {
            $scope.campaign.environments.push({
                label:      environment.label,
                id:         environment.id,
                is_choosen: false
            });
        }
    }
})();