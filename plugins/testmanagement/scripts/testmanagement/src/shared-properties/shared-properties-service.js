export default SharedPropertiesService;

function SharedPropertiesService() {
    var property = {
        campaign_id: undefined,
        project_id: undefined,
        user: undefined,
        nodejs_server_version: undefined,
        uuid: undefined,
        milestone: undefined,
        trackers_using_list_picker: [],
        csrf_token_campaign_status: undefined,
        file_upload_max_size: 0,
        base_url: "",
        platform_name: "",
        platform_logo_url: "",
        project_name: "",
        user_timezone: "",
        user_locale: "",
        artifact_links_types: [],
        is_ordered_by_test_def_rank: false,
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
        getCSRFTokenCampaignStatus,
        setCSRFTokenCampaignStatus,
        setFileUploadMaxSize,
        getFileUploadMaxSize,
        setArtifactLinksTypes,
        getArtifactLinksTypes,
        setIsOrderedByTestDefinitionRank,
        isOrderedByTestDefinitionRank,
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

    function getCSRFTokenCampaignStatus() {
        return property.csrf_token_campaign_status;
    }

    function setCSRFTokenCampaignStatus(csrf_token) {
        property.csrf_token_campaign_status = csrf_token;
    }

    function setFileUploadMaxSize(file_upload_max_size) {
        property.file_upload_max_size = file_upload_max_size;
    }

    function getFileUploadMaxSize() {
        return property.file_upload_max_size;
    }

    function setArtifactLinksTypes(artifact_links_types) {
        property.artifact_links_types = artifact_links_types;
    }

    function getArtifactLinksTypes() {
        return property.artifact_links_types;
    }

    function setIsOrderedByTestDefinitionRank(is_ordered_by_test_def_rank) {
        this.is_ordered_by_test_def_rank = is_ordered_by_test_def_rank;
    }

    function isOrderedByTestDefinitionRank() {
        return this.is_ordered_by_test_def_rank;
    }
}
