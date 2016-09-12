angular
    .module('shared-properties')
    .service('SharedPropertiesService', SharedPropertiesService);

SharedPropertiesService.$inject = [];

function SharedPropertiesService() {
    var property = {
        readyPromise         : null,
        repository_id        : null,
        pull_request         : null,
        user_id              : null,
        nb_pull_request_badge: null,
        nb_pull_requests     : null
    };

    return {
        whenReady            : whenReady,
        setReadyPromise      : setReadyPromise,
        getRepositoryId      : getRepositoryId,
        setRepositoryId      : setRepositoryId,
        getPullRequest       : getPullRequest,
        setPullRequest       : setPullRequest,
        getUserId            : getUserId,
        setUserId            : setUserId,
        getNbPullRequestBadge: getNbPullRequestBadge,
        setNbPullRequestBadge: setNbPullRequestBadge,
        getNbPullRequest     : getNbPullRequest,
        setNbPullRequest     : setNbPullRequest
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

    function getNbPullRequestBadge() {
        return property.nb_pull_request_badge;
    }

    function setNbPullRequestBadge(nb_pull_request_badge) {
        property.nb_pull_request_badge = nb_pull_request_badge;
    }

    function getNbPullRequest() {
        return property.nb_pull_requests;
    }

    function setNbPullRequest(nb_pull_requests) {
        property.nb_pull_requests = parseInt(nb_pull_requests, 10);
    }
}
