export default FileDiffController;

FileDiffController.$inject = ["$state", "SharedPropertiesService"];

function FileDiffController($state, SharedPropertiesService) {
    const self = this;

    Object.assign(self, {
        file_path: $state.params.file_path,
        pull_request: SharedPropertiesService.getPullRequest()
    });
}
