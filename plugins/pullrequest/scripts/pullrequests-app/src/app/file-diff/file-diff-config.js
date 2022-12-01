export default FileDiffConfig;

FileDiffConfig.$inject = ["$stateProvider"];

function FileDiffConfig($stateProvider) {
    $stateProvider.state("diff", {
        url: "/diff-{file_path}/:comment_id",
        parent: "files",
        views: {
            "file-diff@files": {
                template: '<file-diff class="pull-request-file-diff"></file-diff>',
            },
        },
        params: {
            comment_id: {
                squash: true,
                value: null,
            },
        },
    });
}
