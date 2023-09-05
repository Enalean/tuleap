import { sprintf } from "sprintf-js";
import moment from "moment";
import { RelativeDateHelper } from "../helpers/date-helpers";

export default CommitsController;

CommitsController.$inject = [
    "$state",
    "$window",
    "gettextCatalog",
    "CommitsRestService",
    "SharedPropertiesService",
];

function CommitsController(
    $state,
    $window,
    gettextCatalog,
    CommitsRestService,
    SharedPropertiesService,
) {
    const self = this;

    Object.assign(self, {
        $state,
        pull_request: {},
        list: [],
        is_loading_commits: true,
        shouldDisplayWarningMessage,
        shouldDisplayListOfCommits,
        relative_date_helper: RelativeDateHelper(
            SharedPropertiesService.getDateTimeFormat(),
            SharedPropertiesService.getRelativeDateDisplay(),
            SharedPropertiesService.getUserLocale(),
        ),
        $onInit: init,
    });

    function init() {
        SharedPropertiesService.whenReady()
            .then(() => {
                self.pull_request = SharedPropertiesService.getPullRequest();
                return getCommits();
            })
            .catch(function () {
                //Do nothing
            })
            .finally(() => {
                self.is_loading_commits = false;
            });
    }

    function shouldDisplayWarningMessage() {
        if (self.is_loading_commits) {
            return false;
        }

        return self.list.length === 0;
    }

    function shouldDisplayListOfCommits() {
        if (self.is_loading_commits) {
            return false;
        }

        return self.list.length > 0;
    }

    function getCommits() {
        return CommitsRestService.getPaginatedCommits(self.pull_request.id, 50, 0, (response) => {
            self.list = self.list.concat(response.data.map(augmentMetadata));
        });
    }

    function augmentMetadata(commit) {
        let avatar_url = null;
        let display_name = commit.author_name;

        if (commit.author) {
            avatar_url = commit.author.avatar_url;
            display_name = commit.author.display_name;
        }

        const goToAuthor = ($event) => {
            $event.preventDefault();
            $window.location.href = commit.author.user_url;
        };

        const isCommitStatus = (name) => commit.commit_status && commit.commit_status.name === name;
        const isCommitStatusASuccess = () => isCommitStatus("success");
        const isCommitStatusAFailure = () => isCommitStatus("failure");
        const getCommitStatusMessage = () => {
            const message = isCommitStatusASuccess()
                ? gettextCatalog.getString("Continuous integration status: Success on %s")
                : gettextCatalog.getString("Continuous integration status: Failure on %s");

            return sprintf(message, moment(commit.commit_status.date).format("YYYY-MM-DD HH:mm"));
        };

        return {
            short_id: commit.id.substring(0, 10),
            avatar_url,
            display_name,
            goToAuthor,
            isCommitStatusASuccess,
            isCommitStatusAFailure,
            getCommitStatusMessage,
            ...commit,
        };
    }
}
