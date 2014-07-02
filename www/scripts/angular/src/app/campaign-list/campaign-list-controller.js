var campaign_list_controller = function ($scope, service) {

    $scope.init = function(campaign_tracker_id) {
        $scope.list = service.campaigns(campaign_tracker_id);
    };
};