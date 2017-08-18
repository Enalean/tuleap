import _ from 'lodash';

export default ExecutionLinkIssueCtrl;

ExecutionLinkIssueCtrl.$inject = [
    'modal_instance',
    'modal_model',
    'modal_callback',
    'ExecutionRestService'
];

function ExecutionLinkIssueCtrl(
    modal_instance,
    modal_model,
    modal_callback,
    ExecutionRestService
) {
    var self           = this,
        test_execution = modal_model.test_execution;

    _.extend(self, {
        issue: {
            id: ''
        },
        error_message      : null,
        linking_in_progress: false,
        test_summary       : test_execution.definition.summary,
        linkIssue          : linkIssue
    });

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
