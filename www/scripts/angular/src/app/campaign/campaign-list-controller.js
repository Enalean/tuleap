angular
    .module('campaign')
    .controller('CampaignListCtrl', CampaignListCtrl);

CampaignListCtrl.$inject = ['$scope', 'CampaignService', 'SharedPropertiesService'];

function CampaignListCtrl($scope, CampaignService, SharedPropertiesService) {
    var project_id      = SharedPropertiesService.getProjectId();
    var total_campaigns = 0;

    $scope.loading   = true;
    $scope.campaigns = [];

    getCampaigns(project_id, 10, 0);

    function getCampaigns(project_id, limit, offset) {
        CampaignService.getCampaigns(project_id, limit, offset).then(function(data) {
            $scope.campaigns = $scope.campaigns.concat(data.results);
            total_campaigns  = data.total;

            if ($scope.campaigns.length < total_campaigns) {
                getCampaigns(project_id, limit, offset + limit);
            } else {
                $scope.loading = false;
            }
        });
    }
}