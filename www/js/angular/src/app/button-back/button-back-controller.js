angular
    .module('tuleap.pull-request')
    .controller('ButtonBackController', ButtonBackController);

ButtonBackController.$inject = [
    'lodash',
    'SharedPropertiesService'
];

function ButtonBackController(
    _,
    SharedPropertiesService
) {
    var self = this;

    _.extend(self, {
        nb_pull_request_badge       : SharedPropertiesService.getNbPullRequestBadge(),
        nb_pull_requests            : SharedPropertiesService.getNbPullRequest(),
        isThereAtLeastOnePullRequest: isThereAtLeastOnePullRequest
    });

    function isThereAtLeastOnePullRequest() {
        return self.nb_pull_requests > 0;
    }
}
