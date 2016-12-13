angular
    .module('tuleap.pull-request')
    .controller('ButtonBackController', ButtonBackController);

ButtonBackController.$inject = [
    'SharedPropertiesService'
];

function ButtonBackController(
    SharedPropertiesService
) {
    var self = this;

    self.nb_pull_request_badge        = SharedPropertiesService.getNbPullRequestBadge();
    self.nb_pull_requests             = SharedPropertiesService.getNbPullRequest();
    self.isThereAtLeastOnePullRequest = isThereAtLeastOnePullRequest;

    function isThereAtLeastOnePullRequest() {
        return self.nb_pull_requests > 0;
    }
}
