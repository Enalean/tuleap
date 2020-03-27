export default FilesConfig;

FilesConfig.$inject = ["$stateProvider"];

function FilesConfig($stateProvider) {
    $stateProvider.state("files", {
        url: "/",
        template: "<div files></div>",
    });
}
