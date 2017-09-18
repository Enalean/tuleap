angular
    .module('tuleap.pull-request')
    .controller('PullRequestHeaderController', PullRequestHeaderController);

PullRequestHeaderController.$inject = [
    'SharedPropertiesService'
];

function PullRequestHeaderController(
    SharedPropertiesService
) {
    var self = this;

    SharedPropertiesService.whenReady().then(function() {
        self.pull_request = SharedPropertiesService.getPullRequest();
    })
    .catch(function() {
        //Do nothing
    });
}
