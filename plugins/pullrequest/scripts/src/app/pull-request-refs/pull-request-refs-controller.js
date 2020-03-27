export default PullRequestRefsController;

PullRequestRefsController.$inject = ["SharedPropertiesService"];

function PullRequestRefsController(SharedPropertiesService) {
    const self = this;

    Object.assign(self, {
        isCurrentRepository,
        isRepositoryAFork,
    });

    function isCurrentRepository(repository) {
        if (!repository) {
            return false;
        }

        return repository.id === SharedPropertiesService.getRepositoryId();
    }

    function isRepositoryAFork() {
        if (!self.pull_request.repository) {
            return false;
        }

        return self.pull_request.repository.id !== self.pull_request.repository_dest.id;
    }
}
