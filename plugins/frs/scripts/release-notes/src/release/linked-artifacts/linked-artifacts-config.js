export default LinkedArtifactsConfig;

LinkedArtifactsConfig.$inject = ["$stateProvider"];

function LinkedArtifactsConfig($stateProvider) {
    $stateProvider.state("linked-artifacts", {
        url: "/linked-artifacts",
        template: "<div linked-artifacts></div>",
    });
}
