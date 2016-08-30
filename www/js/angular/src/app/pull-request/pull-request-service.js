angular
    .module('tuleap.pull-request')
    .service('PullRequestService', PullRequestService);

PullRequestService.$inject = [
    'lodash',
    'PullRequestRestService'
];

function PullRequestService(
    _,
    PullRequestRestService
) {
    var self = this;

    _.extend(self, {
        valid_status_keys: {
            review : 'review',
            merge  : 'merge',
            abandon: 'abandon'
        },
        merge                    : merge,
        abandon                  : abandon,
        updateTitleAndDescription: updateTitleAndDescription
    });

    function merge(pull_request) {
        return PullRequestRestService.updateStatus(pull_request.id, self.valid_status_keys.merge).then(function() {
            pull_request.status = self.valid_status_keys.merge;
        });
    }

    function abandon(pull_request) {
        return PullRequestRestService.updateStatus(pull_request.id, self.valid_status_keys.abandon).then(function() {
            pull_request.status = self.valid_status_keys.abandon;
        });
    }

    function updateTitleAndDescription(pull_request, new_title, new_description) {
        return PullRequestRestService.updateTitleAndDescription(pull_request.id, new_title, new_description).then(function(response) {
            pull_request.title = response.data.title;
            pull_request.description = response.data.description;
        });
    }
}
