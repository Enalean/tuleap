angular
    .module('shared-properties')
    .service('SharedPropertiesService', SharedPropertiesService);

SharedPropertiesService.$inject = [];

function SharedPropertiesService() {
    var property = {
        readyPromise : null,
        repository_id: null,
        pull_request : null,
        user_id      : null
    };

    return {
        whenReady      : whenReady,
        setReadyPromise: setReadyPromise,
        getRepositoryId: getRepositoryId,
        setRepositoryId: setRepositoryId,
        getPullRequest : getPullRequest,
        setPullRequest : setPullRequest,
        getUserId      : getUserId,
        setUserId      : setUserId
    };

    function whenReady() {
        return property.readyPromise;
    }

    function setReadyPromise(promise) {
        property.readyPromise = promise;
    }

    function getRepositoryId() {
        return property.repository_id;
    }

    function setRepositoryId(repository_id) {
        property.repository_id = parseInt(repository_id, 10);
    }

    function getPullRequest() {
        return property.pull_request;
    }

    function setPullRequest(pull_request) {
        property.pull_request = pull_request;
    }

    function setUserId(user_id) {
        property.user_id = parseInt(user_id, 10);
    }

    function getUserId() {
        return property.user_id;
    }
}
