import { buildHomepageUrl } from "./helpers/homepage-url-builder";
import { getLocaleOrThrow, getTimezoneOrThrow } from "@tuleap/date-helper";

export default MainController;

MainController.$inject = [
    "$element",
    "$document",
    "$state",
    "gettextCatalog",
    "SharedPropertiesService",
];

function MainController($element, $document, $state, gettextCatalog, SharedPropertiesService) {
    this.$onInit = init;

    function init() {
        const pullrequest_init_data = $element[0].querySelector(".pullrequest-init-data").dataset;

        const project_id = pullrequest_init_data.projectId;
        SharedPropertiesService.setProjectId(project_id);

        const repository_id = pullrequest_init_data.repositoryId;
        SharedPropertiesService.setRepositoryId(repository_id);

        const user_id = pullrequest_init_data.userId;
        SharedPropertiesService.setUserId(user_id);

        const nb_pull_request_badge = pullrequest_init_data.nbPullRequestBadge;
        SharedPropertiesService.setNbPullRequestBadge(nb_pull_request_badge);

        const is_there_at_least_one_pull_request =
            pullrequest_init_data.isThereAtLeastOnePullRequest;
        SharedPropertiesService.setIsThereAtLeastOnePullRequest(is_there_at_least_one_pull_request);

        const language = pullrequest_init_data.language;
        initLocale(language);

        const is_merge_commit_allowed = pullrequest_init_data.isMergeCommitAllowed;
        SharedPropertiesService.setIsMergeCommitAllowed(is_merge_commit_allowed);

        const relative_date_display = pullrequest_init_data.relativeDateDisplay;
        SharedPropertiesService.setRelativeDateDisplay(relative_date_display);

        SharedPropertiesService.setTimezone(getTimezoneOrThrow(document));
        SharedPropertiesService.setUserLocale(getLocaleOrThrow(document));
        SharedPropertiesService.setUserAvatarUrl(pullrequest_init_data.userAvatarUrl);

        useUiRouterInPullRequestTabLink(project_id, repository_id);
    }

    function initLocale(language) {
        gettextCatalog.setCurrentLanguage(language);
    }

    function useUiRouterInPullRequestTabLink(project_id, repository_id) {
        const tab_element = $document[0].getElementById("tabs-pullrequest");

        tab_element.href = buildHomepageUrl(window.location, project_id, repository_id);
    }
}
