import { moveBreadCrumbs } from "./move-breadcrumb.js";

export default TestManagementCtrl;

TestManagementCtrl.$inject = [
    "$element",
    "amMoment",
    "gettextCatalog",
    "SharedPropertiesService",
    "UUIDGeneratorService",
];

function TestManagementCtrl(
    $element,
    amMoment,
    gettextCatalog,
    SharedPropertiesService,
    UUIDGeneratorService
) {
    this.$onInit = function () {
        const testmanagement_init_data = $element[0].querySelector(".testmanagement-init-data")
            .dataset;

        const uuid = UUIDGeneratorService.generateUUID();
        SharedPropertiesService.setUUID(uuid);
        SharedPropertiesService.setNodeServerVersion("2.0.0");
        const nodejs_server = testmanagement_init_data.nodejsServer;
        SharedPropertiesService.setNodeServerAddress(nodejs_server);
        var current_user = JSON.parse(testmanagement_init_data.currentUser);
        current_user.uuid = uuid;
        SharedPropertiesService.setCurrentUser(current_user);
        const project_id = testmanagement_init_data.projectId;
        SharedPropertiesService.setProjectId(project_id);
        const tracker_ids = JSON.parse(testmanagement_init_data.trackerIds);
        SharedPropertiesService.setCampaignTrackerId(tracker_ids.campaign_tracker_id);
        SharedPropertiesService.setDefinitionTrackerId(tracker_ids.definition_tracker_id);
        SharedPropertiesService.setExecutionTrackerId(tracker_ids.execution_tracker_id);
        SharedPropertiesService.setIssueTrackerId(tracker_ids.issue_tracker_id);
        const issue_tracker_config = JSON.parse(testmanagement_init_data.issueTrackerConfig);
        SharedPropertiesService.setIssueTrackerConfig(issue_tracker_config);
        const current_milestone = JSON.parse(testmanagement_init_data.currentMilestone);
        SharedPropertiesService.setCurrentMilestone(current_milestone);

        const language = testmanagement_init_data.language;
        amMoment.changeLocale(language);
        gettextCatalog.setCurrentLanguage(language);

        moveBreadCrumbs();
    };
}
