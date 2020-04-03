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
    };

    return {
        getProjectId: getProjectId,
        setProjectId: setProjectId,
        getCampaignId: getCampaignId,
        setCampaignId: setCampaignId,
        getCurrentUser: getCurrentUser,
        setCurrentUser: setCurrentUser,
        getNodeServerAddress: getNodeServerAddress,
        setNodeServerAddress: setNodeServerAddress,
        getUUID: getUUID,
        setUUID: setUUID,
        setNodeServerVersion: setNodeServerVersion,
        getNodeServerVersion: getNodeServerVersion,
        setCampaignTrackerId: setCampaignTrackerId,
        getCampaignTrackerId: getCampaignTrackerId,
        setDefinitionTrackerId: setDefinitionTrackerId,
        getDefinitionTrackerId: getDefinitionTrackerId,
        setExecutionTrackerId: setExecutionTrackerId,
        getExecutionTrackerId: getExecutionTrackerId,
        setIssueTrackerId: setIssueTrackerId,
        getIssueTrackerId: getIssueTrackerId,
        setIssueTrackerConfig: setIssueTrackerConfig,
        getIssueTrackerConfig: getIssueTrackerConfig,
        getCurrentMilestone: getCurrentMilestone,
        setCurrentMilestone: setCurrentMilestone,
    };

    function getProjectId() {
        return property.project_id;
    }

    function setProjectId(project_id) {
        property.project_id = project_id;
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
}
