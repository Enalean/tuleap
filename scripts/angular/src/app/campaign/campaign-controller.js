export default CampaignCtrl;

CampaignCtrl.$inject = ["$scope", "gettextCatalog", "SharedPropertiesService"];

function CampaignCtrl($scope, gettextCatalog, SharedPropertiesService) {
    $scope.milestone = SharedPropertiesService.getCurrentMilestone();
    $scope.campaign_breadcrumb_label = gettextCatalog.getString("Campaigns");
}
