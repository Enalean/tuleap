angular
    .module('tuleap.pull-request')
    .controller('PullRequestController', PullRequestController);

PullRequestController.$inject = [
    'lodash',
    'SharedPropertiesService',
    'PullRequestRestService'
];

function PullRequestController(
    lodash,
    SharedPropertiesService,
    PullRequestRestService
) {
    var self = this;

    lodash.extend(self, {
        pull_request: SharedPropertiesService.getPullRequest(),
        merge       : merge,
        abandon     : abandon
    });

    refreshPullRequest();

    function refreshPullRequest() {
        if (self.pull_request.status === '') {
            PullRequestRestService.getPullRequest(self.pull_request.id).then(function(pull_request) {
                self.pull_request = pull_request;
            });
        }
    }

    function merge() {
        // PullRequestRestService.merge(self.pull_request.id).then(function(response) {
        //     self.pull_request.status = 'M';
        //
        // }).catch(function(response) {
        //     ErrorModalService.showError(response);
        // });
    }

    function abandon() {
        // PullRequestRestService.abandon(self.pull_request.id).then(function(response) {
        //     self.pull_request.status = 'A';
        //
        // }).catch(function(response) {
        //     ErrorModalService.showError(response);
        // });
    }
}
