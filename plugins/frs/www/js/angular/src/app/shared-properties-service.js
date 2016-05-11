angular
    .module('tuleap.frs')
    .service('SharedPropertiesService', SharedPropertiesService);

function SharedPropertiesService() {
    var property = {
        project_id: null,
        release   : null
    };

    return {
        getProjectId: getProjectId,
        setProjectId: setProjectId,
        getRelease  : getRelease,
        setRelease  : setRelease
    };

    function getProjectId() {
        return property.project_id;
    }

    function setProjectId(project_id) {
        property.project_id = project_id;
    }

    function getRelease() {
        return property.release;
    }

    function setRelease(release) {
        property.release = release;
    }
}
