import { moveBreadCrumbs } from "./move-breadcrumb.js";
import { replaceSkipToMainContentLink } from "./keyboard-navigation/replace-skip-to-main-content-link";

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
    UUIDGeneratorService,
) {
    this.$onInit = function () {
        const mount_point = $element[0];
        const body = mount_point.closest("body");
        const testmanagement_init_data = mount_point.querySelector(
            ".testmanagement-init-data",
        ).dataset;

        const uuid = UUIDGeneratorService.generateUUID();
        SharedPropertiesService.setUUID(uuid);
        SharedPropertiesService.setNodeServerVersion("2.0.0");
        var current_user = JSON.parse(testmanagement_init_data.currentUser);
        current_user.uuid = uuid;
        SharedPropertiesService.setCurrentUser(current_user);
        SharedPropertiesService.setUserLocale(body.dataset.userLocale || "en_US");
        SharedPropertiesService.setUserTimezone(body.dataset.userTimezone || "UTC");
        const project_id = testmanagement_init_data.projectId;
        SharedPropertiesService.setProjectId(project_id);
        const base_url = testmanagement_init_data.baseUrl;
        SharedPropertiesService.setBaseUrl(base_url);
        const platform_name = testmanagement_init_data.platformName;
        SharedPropertiesService.setPlatformName(platform_name);
        const platform_logo_url = testmanagement_init_data.platformLogoUrl;
        SharedPropertiesService.setPlatformLogoUrl(platform_logo_url);
        const tracker_ids = JSON.parse(testmanagement_init_data.trackerIds);
        SharedPropertiesService.setCampaignTrackerId(tracker_ids.campaign_tracker_id);
        SharedPropertiesService.setDefinitionTrackerId(tracker_ids.definition_tracker_id);
        SharedPropertiesService.setExecutionTrackerId(tracker_ids.execution_tracker_id);
        SharedPropertiesService.setIssueTrackerId(tracker_ids.issue_tracker_id);
        const issue_tracker_config = JSON.parse(testmanagement_init_data.issueTrackerConfig);
        SharedPropertiesService.setIssueTrackerConfig(issue_tracker_config);
        const current_milestone = JSON.parse(testmanagement_init_data.currentMilestone);
        SharedPropertiesService.setCurrentMilestone(current_milestone);

        const project_public_name = testmanagement_init_data.projectPublicName;
        SharedPropertiesService.setProjectName(project_public_name);
        const project_url = testmanagement_init_data.projectUrl;
        const project_icon = testmanagement_init_data.projectIcon;
        const ttm_admin_url = testmanagement_init_data.ttmAdminUrl;
        const ttm_admin_label = testmanagement_init_data.ttmAdminLabel;

        const csrf_token = testmanagement_init_data.csrfTokenCampaignStatus;
        SharedPropertiesService.setCSRFTokenCampaignStatus(csrf_token);

        const language = testmanagement_init_data.language;
        amMoment.changeLocale(language);
        gettextCatalog.setCurrentLanguage(language);

        const file_upload_max_size = Number.parseInt(
            testmanagement_init_data.fileUploadMaxSize,
            10,
        );
        SharedPropertiesService.setFileUploadMaxSize(file_upload_max_size);
        SharedPropertiesService.setArtifactLinksTypes(
            JSON.parse(testmanagement_init_data.artifactLinksTypes),
        );

        moveBreadCrumbs(
            project_public_name,
            project_url,
            project_icon,
            ttm_admin_url,
            ttm_admin_label,
        );
        replaceSkipToMainContentLink();
    };
}
