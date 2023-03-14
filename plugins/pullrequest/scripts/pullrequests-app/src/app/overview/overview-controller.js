import { createDropdown } from "@tuleap/tlp-dropdown";
import { RelativeDateHelper } from "../helpers/date-helpers.ts";

export default OverviewController;

OverviewController.$inject = [
    "$q",
    "$state",
    "SharedPropertiesService",
    "PullRequestService",
    "UserRestService",
    "MergeModalService",
    "EditModalService",
    "TooltipService",
    "$element",
];

function OverviewController(
    $q,
    $state,
    SharedPropertiesService,
    PullRequestService,
    UserRestService,
    MergeModalService,
    EditModalService,
    TooltipService,
    $element
) {
    const self = this;

    Object.assign(self, {
        author: {},
        editionForm: {},
        operationInProgress: false,
        pull_request: {},
        valid_status_keys: PullRequestService.valid_status_keys,
        current_checkout_method: "ssh",

        getCloneUrl: (method) =>
            self.pull_request.repository_dest
                ? self.pull_request.repository_dest["clone_" + method + "_url"]
                : "",
        abandon,
        reopen,
        buildStatusIs,
        checkMerge,
        hasAbandonRight,
        hasMergeRight,
        hasReopenRight,
        isAbandoned,
        initCheckoutDropdown,
        isConflictingMerge,
        isNonFastForwardMerge,
        isUnknownMerge,
        isSameReferencesMerge,
        showEditionForm,
        relative_date_helper: RelativeDateHelper(
            SharedPropertiesService.getDateTimeFormat(),
            SharedPropertiesService.getRelativeDateDisplay(),
            SharedPropertiesService.getUserLocale()
        ),
        is_preference_set_to_absolute:
            SharedPropertiesService.getRelativeDateDisplay() === "absolute_first-relative_shown" ||
            SharedPropertiesService.getRelativeDateDisplay() === "absolute_first-relative_tooltip",
        is_preference_set_to_relative:
            SharedPropertiesService.getRelativeDateDisplay() === "relative_first-absolute_shown" ||
            SharedPropertiesService.getRelativeDateDisplay() === "relative_first-absolute_tooltip",
        $onInit: init,
    });

    function init() {
        SharedPropertiesService.whenReady()
            .then(function () {
                self.initCheckoutDropdown();

                self.pull_request = SharedPropertiesService.getPullRequest();
                self.is_merge_commit_allowed = SharedPropertiesService.isMergeCommitAllowed();

                self.current_checkout_method = self.pull_request.repository_dest.clone_ssh_url
                    ? "ssh"
                    : "http";

                self.editionForm.raw_title = self.pull_request.raw_title;
                self.editionForm.raw_description = self.pull_request.raw_description;

                UserRestService.getUser(self.pull_request.user_id).then(function (user) {
                    self.author = user;
                });

                TooltipService.setupTooltips($element[0]);
            })
            .catch(function () {
                $state.go("dashboard");
            });
    }

    function showEditionForm() {
        EditModalService.showEditModal(self.pull_request);
    }

    function buildStatusIs(status) {
        return self.pull_request.last_build_status === status;
    }

    function isOpen() {
        return self.pull_request.status === self.valid_status_keys.review;
    }

    function isAbandoned() {
        return self.pull_request.status === self.valid_status_keys.abandon;
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

    function isSameReferencesMerge() {
        return self.pull_request.reference_src === self.pull_request.reference_dest && isOpen();
    }

    function hasMergeRight() {
        return self.pull_request.user_can_merge && isOpen();
    }

    function hasAbandonRight() {
        return self.pull_request.user_can_abandon && isOpen();
    }

    function hasReopenRight() {
        return self.pull_request.user_can_reopen && isAbandoned();
    }

    async function checkMerge() {
        const is_fast_forward = !isNonFastForwardMerge();

        if (is_fast_forward) {
            return merge();
        }

        const should_continue_merge = await MergeModalService.showMergeModal();

        if (should_continue_merge) {
            return merge();
        }
    }

    function merge() {
        self.operationInProgress = true;
        PullRequestService.merge(self.pull_request).then(function () {
            self.operationInProgress = false;
        });
    }

    function abandon() {
        self.operationInProgress = true;
        PullRequestService.abandon(self.pull_request).then(function () {
            self.operationInProgress = false;
        });
    }

    function reopen() {
        self.operationInProgress = true;
        PullRequestService.reopen(self.pull_request).then(function () {
            self.operationInProgress = false;
        });
    }

    function initCheckoutDropdown() {
        createDropdown(document.getElementById("pull-request-checkout-dropdown"), {
            keyboard: false,
        });
    }
}
