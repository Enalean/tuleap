angular
    .module('sharedProperties')
    .service('SharedPropertiesService', SharedPropertiesService);

function SharedPropertiesService() {

    var property = {
        campaign_id          : undefined,
        project_id           : undefined,
        user                 : undefined,
        nodejs_server        : undefined,
        nodejs_server_version: undefined,
        uuid                 : undefined
    };

    return {
        getProjectId        : getProjectId,
        setProjectId        : setProjectId,
        getCampaignId       : getCampaignId,
        setCampaignId       : setCampaignId,
        getCurrentUser      : getCurrentUser,
        setCurrentUser      : setCurrentUser,
        getNodeServerAddress: getNodeServerAddress,
        setNodeServerAddress: setNodeServerAddress,
        getUUID             : getUUID,
        setUUID             : setUUID,
        setNodeServerVersion: setNodeServerVersion,
        getNodeServerVersion: getNodeServerVersion
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

    function setUUID(uuid){
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
}