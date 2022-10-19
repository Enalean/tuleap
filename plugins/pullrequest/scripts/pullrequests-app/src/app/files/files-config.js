export default FilesConfig;

FilesConfig.$inject = ["$stateProvider"];

function FilesConfig($stateProvider) {
    $stateProvider.state("files", {
        url: "/files",
        parent: "pull-request",
        views: {
            "files@pull-request": {
                template: '<div files id="files"></div>',
            },
        },
    });
}
