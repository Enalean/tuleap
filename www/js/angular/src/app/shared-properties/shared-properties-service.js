angular
    .module('shared-properties')
    .service('SharedPropertiesService', SharedPropertiesService);

SharedPropertiesService.$inject = ['$state'];

function SharedPropertiesService($state) {
    var property = {
        repository_id: null,
        pull_request : null,
        user_id      : null
    };

    return {
        getRepositoryId: getRepositoryId,
        setRepositoryId: setRepositoryId,
        getPullRequest : getPullRequest,
        setPullRequest : setPullRequest,
        getUserId      : getUserId,
        setUserId      : setUserId
    };

    function getRepositoryId() {
        return property.repository_id;
    }

    function setRepositoryId(repository_id) {
        property.repository_id = repository_id;
    }

    function getPullRequest() {
        if (! property.pull_request) {
            property.pull_request = {
                id: parseInt($state.params.id, 10)
            };
        }

        return property.pull_request;
    }

    function setPullRequest(pull_request) {
        property.pull_request = pull_request;
    }

    function setUserId(user_id) {
        property.user_id = user_id;
    }

    function getUserId() {
        return parseInt(property.user_id, 10);
    }
}
