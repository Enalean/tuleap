var campaign_list_controller = function ($scope, service, sharedPropertiesService) {
    var campaign_tracker_id = sharedPropertiesService.getCampaignTrackerId();
    $scope.list = service.campaigns(campaign_tracker_id);
};