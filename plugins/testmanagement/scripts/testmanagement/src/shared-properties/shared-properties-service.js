export default SharedPropertiesService;

function SharedPropertiesService() {
    var property = {
        campaign_id: undefined,
        project_id: undefined,
        user: undefined,
        nodejs_server: undefined,
        nodejs_server_version: undefined,
        uuid: undefined,
        milestone: undefined,
        trackers_using_list_picker: [],
        csrf_token_campaign_status: undefined,
        has_current_project_parents: false,
        file_upload_max_size: 0,
        is_links_field_v2_enabled: false,
        base_url: "",
        platform_name: "",
        platform_logo_url: "",
        project_name: "",
        user_timezone: "",
        user_locale: "",
    };

    return {
        getPlatformName,
        setPlatformName,
        getPlatformLogoUrl,
        setPlatformLogoUrl,
        getBaseUrl,
        setBaseUrl,
        getProjectName,
        setProjectName,
        getProjectId,
        setProjectId,
        getCampaignId,
        setCampaignId,
        getCurrentUser,
        setCurrentUser,
        getUserLocale,
        setUserLocale,
        getUserTimezone,
        setUserTimezone,
        getNodeServerAddress,
        setNodeServerAddress,
        getUUID,
        setUUID,
        setNodeServerVersion,
        getNodeServerVersion,
        setCampaignTrackerId,
        getCampaignTrackerId,
        setDefinitionTrackerId,
        getDefinitionTrackerId,
        setExecutionTrackerId,
        getExecutionTrackerId,
        setIssueTrackerId,
        getIssueTrackerId,
        setIssueTrackerConfig,
        getIssueTrackerConfig,
        getCurrentMilestone,
        setCurrentMilestone,
        isListPickerUsedByTracker,
        setTrackersUsingListPicker,
        getCSRFTokenCampaignStatus,
        setCSRFTokenCampaignStatus,
        setHasCurrentProjectParents,
        hasCurrentProjectParents,
        setFileUploadMaxSize,
        getFileUploadMaxSize,
        setIsLinksFieldV2Enabled,
        isLinksFieldV2Enabled,
    };

    function getPlatformLogoUrl() {
        return property.platform_logo_url;
    }

    function setPlatformLogoUrl(platform_logo_url) {
        property.platform_logo_url = platform_logo_url;
    }

    function getPlatformName() {
        return property.platform_name;
    }

    function setPlatformName(platform_name) {
        property.platform_name = platform_name;
    }

    function getBaseUrl() {
        return property.base_url;
    }

    function setBaseUrl(base_url) {
        property.base_url = base_url;
    }

    function getProjectName() {
        return property.project_name;
    }

    function setProjectName(project_name) {
        property.project_name = project_name;
    }

    function getProjectId() {
        return property.project_id;
    }

    function setProjectId(project_id) {
        property.project_id = project_id;
    }

    function getUserTimezone() {
        return property.user_timezone;
    }

    function setUserTimezone(user_timezone) {
        property.user_timezone = user_timezone;
    }

    function getUserLocale() {
        return property.user_locale;
    }

    function setUserLocale(user_locale) {
        property.user_locale = user_locale;
    }

    function getCampaignId() {
        return property.campaign_id;
    }

    function setCampaignId(campaign_id) {
        property.campaign_id = campaign_id;
    }

    function getCurrentUser() {
        return property.user;
    }

    function setCurrentUser(user) {
        property.user = user;
    }

    function getNodeServerAddress() {
        return property.nodejs_server;
    }

    function setNodeServerAddress(nodejs_server) {
        property.nodejs_server = nodejs_server;
    }

    function setUUID(uuid) {
        property.uuid = uuid;
    }

    function getUUID() {
        return property.uuid;
    }

    function setNodeServerVersion(nodejs_server_version) {
        property.nodejs_server_version = nodejs_server_version;
    }

    function getNodeServerVersion() {
        return property.nodejs_server_version;
    }

    function setCampaignTrackerId(campaign_tracker_id) {
        property.campaign_tracker_id = campaign_tracker_id;
    }

    function getCampaignTrackerId() {
        return property.campaign_tracker_id;
    }

    function setDefinitionTrackerId(definition_tracker_id) {
        property.definition_tracker_id = definition_tracker_id;
    }

    function getDefinitionTrackerId() {
        return property.definition_tracker_id;
    }

    function setExecutionTrackerId(execution_tracker_id) {
        property.execution_tracker_id = execution_tracker_id;
    }

    function getExecutionTrackerId() {
        return property.execution_tracker_id;
    }

    function setIssueTrackerId(issue_tracker_id) {
        property.issue_tracker_id = issue_tracker_id;
    }

    function getIssueTrackerId() {
        return property.issue_tracker_id;
    }

    function setIssueTrackerConfig(config) {
        property.issue_tracker_config = config;
    }

    function getIssueTrackerConfig() {
        return property.issue_tracker_config;
    }

    function getCurrentMilestone() {
        return property.milestone;
    }

    function setCurrentMilestone(milestone) {
        property.milestone = milestone;
    }

    function isListPickerUsedByTracker(tracker_id) {
        return property.trackers_using_list_picker.includes(tracker_id);
    }

    function setTrackersUsingListPicker(trackers_using_list_picker) {
        property.trackers_using_list_picker = trackers_using_list_picker;
    }

    function getCSRFTokenCampaignStatus() {
        return property.csrf_token_campaign_status;
    }

    function setCSRFTokenCampaignStatus(csrf_token) {
        property.csrf_token_campaign_status = csrf_token;
    }

    function setHasCurrentProjectParents(has_current_project_parents) {
        property.has_current_project_parents = has_current_project_parents;
    }

    function hasCurrentProjectParents() {
        return property.has_current_project_parents;
    }

    function setFileUploadMaxSize(file_upload_max_size) {
        property.file_upload_max_size = file_upload_max_size;
    }

    function getFileUploadMaxSize() {
        return property.file_upload_max_size;
    }

    function setIsLinksFieldV2Enabled(is_links_field_v2_enabled) {
        property.is_links_field_v2_enabled = is_links_field_v2_enabled;
    }

    function isLinksFieldV2Enabled() {
        return property.is_links_field_v2_enabled;
    }
}
