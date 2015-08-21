angular
    .module('sharedProperties')
    .service('SharedPropertiesService', SharedPropertiesService);

SharedPropertiesService.$inject = ['Restangular', '$window', 'UserService'];

function SharedPropertiesService(Restangular, $window, UserService) {
    var baseurl = '/api/v1',
        rest = Restangular.withConfig(setRestangularConfig);

    var property = {
        project_id: undefined,
        user: undefined,
        execution_id: undefined
    };

    return {
        getNodeServerAddress       : getNodeServerAddress,
        setNodeServerAddress       : setNodeServerAddress,
        getProjectId               : getProjectId,
        setProjectId               : setProjectId,
        getCurrentUser             : getCurrentUser,
        setCurrentUser             : setCurrentUser
    };

    function setRestangularConfig(RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl(baseurl);
    }

    function getNodeServerAddress() {
        return property.node_server_address;
    }

    function setNodeServerAddress(node_server_address) {
        property.node_server_address = node_server_address;
    }

    function getProjectId() {
        return property.project_id;
    }

    function setProjectId(project_id) {
        property.project_id = project_id;
    }

    function getCurrentUser() {
        if (typeof property.user === 'undefined') {
            return UserService.getCurrentUserFromCookies();
        }

        return property.user;
    }

    function setCurrentUser(user) {
        property.user = user;
    }
}