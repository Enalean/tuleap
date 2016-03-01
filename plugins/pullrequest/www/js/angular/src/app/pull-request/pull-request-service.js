angular
    .module('tuleap.pull-request')
    .service('PullRequestService', PullRequestService);

PullRequestService.$inject = [
    'lodash',
    'PullRequestRestService'
];

function PullRequestService(
    lodash,
    PullRequestRestService
) {
    var self = this;

    lodash.extend(self, {
        valid_status_keys: {
            review : 'review',
            merge  : 'merge',
            abandon: 'abandon',
        },
        merge            : merge,
        abandon          : abandon
    });

    function merge(pull_request) {
        PullRequestRestService.updateStatus(pull_request.id, self.valid_status_keys.merge).then(function() {
            pull_request.status = self.valid_status_keys.merge;
        });
    }

    function abandon(pull_request) {
        PullRequestRestService.updateStatus(pull_request.id, self.valid_status_keys.abandon).then(function() {
            pull_request.status = self.valid_status_keys.abandon;
        });
    }
}
