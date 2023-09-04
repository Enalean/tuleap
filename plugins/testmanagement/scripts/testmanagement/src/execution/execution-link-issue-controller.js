export default ExecutionLinkIssueCtrl;

ExecutionLinkIssueCtrl.$inject = [
    "$scope",
    "$q",
    "modal_instance",
    "modal_model",
    "modal_callback",
    "ExecutionRestService",
    "SharedPropertiesService",
];

function ExecutionLinkIssueCtrl(
    $scope,
    $q,
    modal_instance,
    modal_model,
    modal_callback,
    ExecutionRestService,
    SharedPropertiesService,
) {
    const self = this,
        { test_execution } = modal_model,
        issue_tracker_id = SharedPropertiesService.getIssueTrackerId(),
        issue_xref_color =
            SharedPropertiesService.getIssueTrackerConfig().xref_color || "secondary";

    Object.assign(self, {
        issue: {
            id: "",
        },
        issue_artifact: null,
        error_message: null,
        issue_debounce: 500,
        linking_in_progress: false,
        test_summary: test_execution.definition.summary,
        $onInit,
        linkIssue,
        validateIssueId,
        validateIssueIsNotAlreadyLinked,
    });

    function $onInit() {
        const { issue_id } = $scope.link_issue_form;
        issue_id.$validators.issueNotAlreadyLinked = self.validateIssueIsNotAlreadyLinked;
        issue_id.$asyncValidators.validIssueId = self.validateIssueId;

        modal_instance.tlp_modal.addEventListener("tlp-modal-shown", () => {
            const input = modal_instance.tlp_modal.element.querySelector(".link-issue-modal-input");
            if (input) {
                input.focus();
            }
        });
    }

    function validateIssueIsNotAlreadyLinked(model_value, view_value) {
        const index = test_execution.linked_bugs.findIndex(
            (artifact) => artifact.id === Number.parseInt(view_value, 10),
        );
        return index === -1;
    }

    function validateIssueId(model_value, view_value) {
        return ExecutionRestService.getArtifactById(view_value).then((artifact) => {
            if (artifact.tracker.id === issue_tracker_id) {
                artifact.tracker.color_name = issue_xref_color;
                self.issue_artifact = artifact;
                return true;
            } else {
                return $q.reject();
            }
        });
    }

    function linkIssue() {
        self.linking_in_progress = true;
        self.error_message = null;

        ExecutionRestService.linkIssue(self.issue.id, test_execution)
            .then(function () {
                modal_instance.tlp_modal.hide();
                modal_callback(self.issue_artifact);
            })
            .catch(function (error) {
                self.error_message = error.message;
            })
            .finally(function () {
                self.linking_in_progress = false;
            });
    }
}
