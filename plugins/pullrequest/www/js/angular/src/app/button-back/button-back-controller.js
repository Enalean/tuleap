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
    self.isThereAtLeastOnePullRequest = SharedPropertiesService.isThereAtLeastOnePullRequest;
}
