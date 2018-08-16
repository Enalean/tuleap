export default FileDiffConfig;

FileDiffConfig.$inject = ["$stateProvider"];

function FileDiffConfig($stateProvider) {
    $stateProvider.state("diff", {
        url: "/diff-{file_path}",
        parent: "files",
        views: {
            "file-diff@files": {
                template: '<div file-diff id="file-diff"></div>'
            }
        }
    });
}
