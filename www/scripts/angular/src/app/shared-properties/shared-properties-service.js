angular
    .module('sharedProperties')
    .service('SharedPropertiesService', SharedPropertiesService);

function SharedPropertiesService() {
    var property = {
        project_id:   undefined,
        current_user: undefined
    };

    return {
        getProjectId: getProjectId,
        setProjectId: setProjectId,
        getCurrentUser: getCurrentUser,
        setCurrentUser: setCurrentUser
    };

    function getProjectId() {
        return property.project_id;
    }

    function setProjectId(project_id) {
        property.project_id = project_id;
    }

    function getCurrentUser() {
        return property.current_user;
    }

    function setCurrentUser(current_user) {
        property.current_user = current_user;
    }
}