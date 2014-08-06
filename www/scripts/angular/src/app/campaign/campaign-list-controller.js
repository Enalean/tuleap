angular
    .module('campaign')
    .controller('CampaignListCtrl', CampaignListCtrl);

CampaignListCtrl.$inject = ['$scope', 'CampaignService', 'SharedPropertiesService'];

function CampaignListCtrl($scope, CampaignService, SharedPropertiesService) {
    var project_id   = SharedPropertiesService.getProjectId();
    $scope.campaigns = CampaignService.getCampaigns(project_id);
}