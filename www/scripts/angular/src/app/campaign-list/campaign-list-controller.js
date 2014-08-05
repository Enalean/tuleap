var campaign_list_controller = function ($scope, service, sharedPropertiesService) {
    var project_id   = sharedPropertiesService.getProjectId();
    $scope.campaigns = service.campaigns(project_id);
};