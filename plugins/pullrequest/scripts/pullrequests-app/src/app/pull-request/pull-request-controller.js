import { buildOverviewURL } from "../helpers/overview-url-builder";
import { redirectToUrl } from "../window-helper";

export default PullRequestController;

PullRequestController.$inject = ["$state", "PullRequestRestService", "SharedPropertiesService"];

function PullRequestController($state, PullRequestRestService, SharedPropertiesService) {
    const self = this;

    Object.assign(self, {
        $state,
        $onInit: init,
        getOverviewUrl: () => {
            const pull_request = SharedPropertiesService.getPullRequest();
            if (!pull_request) {
                return "";
            }

            return buildOverviewURL(
                window.location,
                pull_request,
                SharedPropertiesService.getProjectId(),
                SharedPropertiesService.getRepositoryId(),
            ).toString();
        },
    });

    function init() {
        const pull_request_id = parseInt($state.params.id, 10);
        const promise = PullRequestRestService.getPullRequest(pull_request_id);

        SharedPropertiesService.setReadyPromise(promise);

        promise.then(function (pullrequest) {
            SharedPropertiesService.setPullRequest(pullrequest);

            if (pullrequest.is_git_reference_broken) {
                redirectToUrl(self.getOverviewUrl());
            }
        });
    }
}
