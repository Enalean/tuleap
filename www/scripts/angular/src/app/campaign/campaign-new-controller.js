(function () {
    angular
        .module('campaign')
        .controller('CampaignNewCtrl', CampaignNewCtrl);

    CampaignNewCtrl.$inject = ['$scope', '$state', 'gettextCatalog', 'CampaignService', 'SharedPropertiesService'];

    function CampaignNewCtrl($scope, $state, gettextCatalog, CampaignService, SharedPropertiesService) {
        var project_id = SharedPropertiesService.getProjectId();

        $scope.campaign = {
            project_id: project_id
        };

        $scope.breadcrumb_label = gettextCatalog.getString('Campaign creation');
        $scope.createCampaign   = createCampaign;

        function createCampaign(campaign) {
            CampaignService
                .createCampaign(campaign)
                .then(function () {
                    $state.go('campaigns.list', {}, {reload: true});
            });
        }
    }
})();