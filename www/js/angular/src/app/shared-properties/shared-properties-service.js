angular
    .module('shared-properties')
    .service('SharedPropertiesService', SharedPropertiesService);

SharedPropertiesService.$inject = [];

function SharedPropertiesService() {
    var property = {
        readyPromise : null,
        repository_id: null,
        pull_request : null,
        pull_requests: [],
        user_id      : null
    };

    return {
        whenReady      : whenReady,
        setReadyPromise: setReadyPromise,
        getRepositoryId: getRepositoryId,
        setRepositoryId: setRepositoryId,
        getPullRequest : getPullRequest,
        setPullRequest : setPullRequest,
        getPullRequests: getPullRequests,
        setPullRequests: setPullRequests,
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
        property.repository_id = repository_id;
    }

    function getPullRequest() {
        return property.pull_request;
    }

    function setPullRequest(pull_request) {
        property.pull_request = pull_request;
    }

    function getPullRequests() {
        return property.pull_requests;
    }

    function setPullRequests(pull_requests) {
        property.pull_requests = pull_requests;
    }

    function setUserId(user_id) {
        property.user_id = user_id;
    }

    function getUserId() {
        return parseInt(property.user_id, 10);
    }
}
