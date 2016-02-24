angular
    .module('shared-properties')
    .service('SharedPropertiesService', SharedPropertiesService);

function SharedPropertiesService($state) {
    var property = {
        repository_id: undefined,
        pull_request : undefined,
        user_id      : undefined
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
        if (property.pull_request === undefined) {
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

    function getUserId(user_id) {
        return parseInt(property.user_id, 10);
    }
}
