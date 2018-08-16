export default ButtonBackController;

ButtonBackController.$inject = ["SharedPropertiesService"];

function ButtonBackController(SharedPropertiesService) {
    const self = this;

    self.nb_pull_request_badge = SharedPropertiesService.getNbPullRequestBadge();
    self.isThereAtLeastOnePullRequest = SharedPropertiesService.isThereAtLeastOnePullRequest;
}
