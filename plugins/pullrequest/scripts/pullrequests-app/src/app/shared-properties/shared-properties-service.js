export default SharedPropertiesService;

SharedPropertiesService.$inject = [];

function SharedPropertiesService() {
    var property = {
        readyPromise: null,
        project_id: null,
        repository_id: null,
        pull_request: null,
        user_id: null,
        user_avatar_url: "",
        nb_pull_request_badge: null,
        is_there_at_least_one_pull_request: null,
        is_merge_commit_allowed: null,
        date_time_format: "",
        user_locale: "",
        relative_date_display: "",
    };

    return {
        whenReady,
        setReadyPromise,
        getProjectId,
        setProjectId,
        getRepositoryId,
        setRepositoryId,
        getPullRequest,
        setPullRequest,
        getUserId,
        setUserId,
        getUserAvatarUrl,
        setUserAvatarUrl,
        getNbPullRequestBadge,
        getDateTimeFormat,
        getUserLocale,
        getRelativeDateDisplay,
        setNbPullRequestBadge,
        isThereAtLeastOnePullRequest,
        setIsThereAtLeastOnePullRequest,
        isMergeCommitAllowed,
        setIsMergeCommitAllowed,
        setDateTimeFormat,
        setUserLocale,
        setRelativeDateDisplay,
    };

    function whenReady() {
        return property.readyPromise;
    }

    function setReadyPromise(promise) {
        property.readyPromise = promise;
    }

    function getProjectId() {
        return property.project_id;
    }

    function setProjectId(repository_id) {
        property.project_id = parseInt(repository_id, 10);
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

    function getDateTimeFormat() {
        return property.date_time_format;
    }

    function getUserLocale() {
        return property.user_locale;
    }

    function getRelativeDateDisplay() {
        return property.relative_date_display;
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

    function setDateTimeFormat(date_time_format) {
        property.date_time_format = date_time_format;
    }

    function setUserLocale(user_locale) {
        property.user_locale = user_locale;
    }

    function setRelativeDateDisplay(relative_date_display) {
        property.relative_date_display = relative_date_display;
    }

    function getUserAvatarUrl() {
        return property.user_avatar_url;
    }

    function setUserAvatarUrl(user_avatar_url) {
        property.user_avatar_url = user_avatar_url;
    }
}
