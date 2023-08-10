export default PullRequestHeaderController;

PullRequestHeaderController.$inject = ["$state", "SharedPropertiesService"];

function PullRequestHeaderController($state, SharedPropertiesService) {
    const self = this;

    Object.assign(self, {
        $onInit,
    });

    function $onInit() {
        SharedPropertiesService.whenReady()
            .then(function () {
                self.pull_request = SharedPropertiesService.getPullRequest();
            })
            .catch(function () {
                //Do nothing
            });
    }
}
