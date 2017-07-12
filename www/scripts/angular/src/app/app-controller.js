export default TrafficlightsCtrl;

TrafficlightsCtrl.$inject = [
    '$scope',
    'amMoment',
    'gettextCatalog',
    'SharedPropertiesService',
    'UUIDGeneratorService'
];

function TrafficlightsCtrl(
    $scope,
    amMoment,
    gettextCatalog,
    SharedPropertiesService,
    UUIDGeneratorService
) {
    $scope.init = function(nodejs_server, project_id, tracker_ids, lang, current_user, current_milestone) {
        var uuid = UUIDGeneratorService.generateUUID();
        SharedPropertiesService.setUUID(uuid);
        SharedPropertiesService.setNodeServerVersion("1.1.0");
        SharedPropertiesService.setNodeServerAddress(nodejs_server);
        current_user.uuid = uuid;
        SharedPropertiesService.setCurrentUser(current_user);
        SharedPropertiesService.setProjectId(project_id);
        SharedPropertiesService.setCampaignTrackerId(tracker_ids.campaign_tracker_id);
        SharedPropertiesService.setDefinitionTrackerId(tracker_ids.definition_tracker_id);
        SharedPropertiesService.setExecutionTrackerId(tracker_ids.execution_tracker_id);
        SharedPropertiesService.setCurrentMilestone(current_milestone);

        amMoment.changeLocale(lang);
        gettextCatalog.setCurrentLanguage(lang);
    };
}
