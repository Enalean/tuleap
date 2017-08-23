import _ from 'lodash';

export default ExecutionLinkIssueCtrl;

ExecutionLinkIssueCtrl.$inject = [
    '$scope',
    'modal_instance',
    'modal_model',
    'modal_callback',
    'ExecutionRestService',
    'SharedPropertiesService'
];

function ExecutionLinkIssueCtrl(
    $scope,
    modal_instance,
    modal_model,
    modal_callback,
    ExecutionRestService,
    SharedPropertiesService
) {
    var self             = this,
        test_execution   = modal_model.test_execution,
        issue_tracker_id = SharedPropertiesService.getIssueTrackerId(),
        issue_xref_color = SharedPropertiesService.getIssueTrackerConfig().xref_color || 'secondary';

    _.extend(self, {
        issue: {
            id        : '',
            label     : '',
            xref      : '',
            xref_color: issue_xref_color
        },
        error_message      : null,
        issue_debounce     : 500,
        linking_in_progress: false,
        test_summary       : test_execution.definition.summary,
        linkIssue          : linkIssue
    });

    self.$onInit = function() {
        $scope.link_issue_form.issue_id.$asyncValidators.validIssueId = function(model_value, view_value) {
            return ExecutionRestService.getArtifactById(view_value).then(function(artifact) {
                if (artifact.tracker.id === issue_tracker_id) {
                    self.issue.xref  = artifact.xref;
                    self.issue.title = artifact.title;
                    return true;
                } else {
                    return Promise.reject();
                }
            });
        }
    };

    function linkIssue() {
        self.linking_in_progress = true;
        self.error_message = null;

        ExecutionRestService
            .linkIssue(self.issue.id, test_execution)
            .then(function () {
                modal_instance.tlp_modal.hide();
                modal_callback(self.issue.id);
            })
            .catch(function (error) {
                self.error_message = error.message;
            })
            .finally(function () {
                self.linking_in_progress = false;
            });
    }
}
