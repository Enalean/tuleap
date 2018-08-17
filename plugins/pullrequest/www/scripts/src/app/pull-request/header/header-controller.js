export default PullRequestHeaderController;

PullRequestHeaderController.$inject = ["SharedPropertiesService"];

function PullRequestHeaderController(SharedPropertiesService) {
    const self = this;

    SharedPropertiesService.whenReady()
        .then(function() {
            self.pull_request = SharedPropertiesService.getPullRequest();
        })
        .catch(function() {
            //Do nothing
        });
}
