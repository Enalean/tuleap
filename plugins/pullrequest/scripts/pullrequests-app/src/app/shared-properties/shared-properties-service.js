export default SharedPropertiesService;

SharedPropertiesService.$inject = [];

function SharedPropertiesService() {
    var property = {
        readyPromise: null,
        repository_id: null,
        pull_request: null,
        user_id: null,
        nb_pull_request_badge: null,
        is_there_at_least_one_pull_request: null,
        is_merge_commit_allowed: null,
    };

    return {
        whenReady,
        setReadyPromise,
        getRepositoryId,
        setRepositoryId,
        getPullRequest,
        setPullRequest,
        getUserId,
        setUserId,
        getNbPullRequestBadge,
        setNbPullRequestBadge,
        isThereAtLeastOnePullRequest,
        setIsThereAtLeastOnePullRequest,
        isMergeCommitAllowed,
        setIsMergeCommitAllowed,
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

    function isThereAtLeastOnePullRequest() {
        return property.is_there_at_least_one_pull_request;
    }

    function setIsThereAtLeastOnePullRequest(is_there_at_least_one_pull_request) {
        property.is_there_at_least_one_pull_request = Boolean(is_there_at_least_one_pull_request);
    }

    function isMergeCommitAllowed() {
        return property.is_merge_commit_allowed;
    }

    function setIsMergeCommitAllowed(is_merge_commit_allowed) {
        property.is_merge_commit_allowed = Boolean(is_merge_commit_allowed);
    }
}
