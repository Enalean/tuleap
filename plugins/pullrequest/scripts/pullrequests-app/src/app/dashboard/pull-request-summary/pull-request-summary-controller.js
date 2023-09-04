import { RelativeDateHelper } from "../../helpers/date-helpers";
import { buildOverviewURL } from "../../helpers/overview-url-builder";

import "./PullRequestLabelsList";

export default PullRequestSummaryController;

PullRequestSummaryController.$inject = [
    "$state",
    "PullRequestService",
    "UserRestService",
    "SharedPropertiesService",
    "ErrorModalService",
];

function PullRequestSummaryController(
    $state,
    PullRequestService,
    UserRestService,
    SharedPropertiesService,
    ErrorModalService,
) {
    const self = this;

    Object.assign(self, {
        author: {},

        $onInit: init,
        goToOverview,
        isAbandoned,
        isMerged,
        relative_date_helper: RelativeDateHelper(
            SharedPropertiesService.getDateTimeFormat(),
            SharedPropertiesService.getRelativeDateDisplay(),
            SharedPropertiesService.getUserLocale(),
        ),
        onFetchErrorCallback: (event) => {
            ErrorModalService.showErrorMessage(event.detail.fault);
        },
    });

    function init() {
        UserRestService.getUser(self.pull_request.user_id).then((user) => {
            self.author = user;
        });
    }

    function isAbandoned() {
        return self.pull_request.status === PullRequestService.valid_status_keys.abandon;
    }

    function isMerged() {
        return self.pull_request.status === PullRequestService.valid_status_keys.merge;
    }

    function goToOverview() {
        window.location.assign(
            buildOverviewURL(
                window.location,
                self.pull_request,
                SharedPropertiesService.getProjectId(),
                SharedPropertiesService.getRepositoryId(),
            ).toString(),
        );
    }
}
