export default OverviewController;

OverviewController.$inject = [
    "$q",
    "$state",
    "SharedPropertiesService",
    "PullRequestService",
    "UserRestService",
    "MergeModalService",
    "TooltipService"
];

function OverviewController(
    $q,
    $state,
    SharedPropertiesService,
    PullRequestService,
    UserRestService,
    MergeModalService,
    TooltipService
) {
    const self = this;

    Object.assign(self, {
        author: {},
        editionForm: {},
        operationInProgress: false,
        pull_request: {},
        showEditionForm: false,
        valid_status_keys: PullRequestService.valid_status_keys,
        current_checkout_method: "ssh",

        getCloneUrl: method =>
            self.pull_request.repository_dest
                ? self.pull_request.repository_dest["clone_" + method + "_url"]
                : "",
        abandon,
        buildStatusIs,
        checkMerge,
        hasAbandonRight,
        hasMergeRight,
        isConflictingMerge,
        isNonFastForwardMerge,
        isUnknownMerge,
        saveEditionForm
    });

    SharedPropertiesService.whenReady()
        .then(function() {
            self.pull_request = SharedPropertiesService.getPullRequest();
            self.is_merge_commit_allowed = SharedPropertiesService.isMergeCommitAllowed();

            self.current_checkout_method = self.pull_request.repository_dest.clone_ssh_url
                ? "ssh"
                : "http";

            self.editionForm.raw_title = self.pull_request.raw_title;
            self.editionForm.raw_description = self.pull_request.raw_description;

            UserRestService.getUser(self.pull_request.user_id).then(function(user) {
                self.author = user;
            });

            TooltipService.setupTooltips();
        })
        .catch(function() {
            $state.go("dashboard");
        });

    function buildStatusIs(status) {
        return self.pull_request.last_build_status === status;
    }

    function saveEditionForm() {
        PullRequestService.updateTitleAndDescription(
            self.pull_request,
            self.editionForm.raw_title,
            self.editionForm.raw_description
        ).then(function() {
            self.showEditionForm = false;
            TooltipService.setupTooltips();
        });
    }

    function isOpen() {
        return self.pull_request.status === self.valid_status_keys.review;
    }

    function isConflictingMerge() {
        return self.pull_request.merge_status === "conflict" && isOpen();
    }

    function isNonFastForwardMerge() {
        return self.pull_request.merge_status === "no_fastforward" && isOpen();
    }

    function isUnknownMerge() {
        return self.pull_request.merge_status === "unknown-merge-status" && isOpen();
    }

    function hasMergeRight() {
        return self.pull_request.user_can_merge && isOpen();
    }

    function hasAbandonRight() {
        return self.pull_request.user_can_abandon && isOpen();
    }

    function checkMerge() {
        var shouldMerge = isNonFastForwardMerge()
            ? MergeModalService.showMergeModal()
            : $q.when("go");
        shouldMerge.then(merge);
    }

    function merge() {
        self.operationInProgress = true;
        PullRequestService.merge(self.pull_request).then(function() {
            self.operationInProgress = false;
        });
    }

    function abandon() {
        self.operationInProgress = true;
        PullRequestService.abandon(self.pull_request).then(function() {
            self.operationInProgress = false;
        });
    }
}
