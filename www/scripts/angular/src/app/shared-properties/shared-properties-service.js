angular
    .module('sharedProperties')
    .service('SharedPropertiesService', SharedPropertiesService);

SharedPropertiesService.$inject = ['Restangular', '$window'];

function SharedPropertiesService(Restangular, $window) {
    var baseurl = '/api/v1',
        rest = Restangular.withConfig(setRestangularConfig);

    var property = {
        project_id: undefined
    };

    return {
        getNodeServerAddress: getNodeServerAddress,
        setNodeServerAddress: setNodeServerAddress,
        getProjectId:   getProjectId,
        setProjectId:   setProjectId,
        getCurrentUser: getCurrentUser,
        setCurrentUser: setCurrentUser
    };

    function getNodeServerAddress() {
        return property.node_server_address;
    }

    function setNodeServerAddress(node_server_address) {
        property.node_server_address = node_server_address;
    }

    function setRestangularConfig(RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl(baseurl);
    }

    function getProjectId() {
        return property.project_id;
    }

    function setProjectId(project_id) {
        property.project_id = project_id;
    }

    function getCurrentUser() {
        return JSON.parse($window.localStorage.getItem('tuleap_user'));
    }

    function setCurrentUser(current_user) {
        $window.localStorage.setItem('tuleap_user', JSON.stringify(current_user));
    }
}