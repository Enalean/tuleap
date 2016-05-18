angular
    .module('tuleap.pull-request')
    .controller('OverviewController', OverviewController);

OverviewController.$inject = [
    'lodash',
    'SharedPropertiesService',
    'PullRequestService',
    'UserRestService'
];

function OverviewController(
    lodash,
    SharedPropertiesService,
    PullRequestService,
    UserRestService
) {
    var self = this;

    lodash.extend(self, {
        valid_status_keys: PullRequestService.valid_status_keys,
        pull_request     : {},
        author           : {},
        merge            : merge,
        abandon          : abandon,
        editionForm      : {},
        showEditionForm  : false,
        saveEditionForm  : saveEditionForm
    });

    SharedPropertiesService.whenReady().then(function() {
        self.pull_request = SharedPropertiesService.getPullRequest();

        self.editionForm.title = self.pull_request.title;
        self.editionForm.description = self.pull_request.description;

        UserRestService.getUser(self.pull_request.user_id).then(function(user) {
            self.author = user;
        });
    });

    function merge() {
        PullRequestService.merge(self.pull_request);
    }

    function abandon() {
        PullRequestService.abandon(self.pull_request);
    }

    function saveEditionForm() {
        PullRequestService.updateTitleAndDescription(
            self.pull_request,
            self.editionForm.title,
            self.editionForm.description)
        .then(function() {
            self.showEditionForm = false;
        });
    }
}
