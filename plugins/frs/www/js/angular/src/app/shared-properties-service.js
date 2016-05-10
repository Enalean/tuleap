angular
    .module('tuleap.frs')
    .service('SharedPropertiesService', SharedPropertiesService);

function SharedPropertiesService() {
    var property = {
        project_id: null,
        release_id: null
    };

    return {
        getProjectId: getProjectId,
        setProjectId: setProjectId,
        getReleaseId: getReleaseId,
        setReleaseId: setReleaseId
    };

    function getProjectId() {
        return property.project_id;
    }

    function setProjectId(project_id) {
        property.project_id = project_id;
    }

    function getReleaseId() {
        return property.release_id;
    }

    function setReleaseId(release_id) {
        property.release_id = release_id;
    }
}
