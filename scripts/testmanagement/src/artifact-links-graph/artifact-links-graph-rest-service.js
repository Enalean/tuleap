export default ArtifactLinksGraphRestService;

ArtifactLinksGraphRestService.$inject = ["Restangular"];

function ArtifactLinksGraphRestService(Restangular) {
    var rest = Restangular.withConfig(function (RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl("/api/v1");
    });

    return {
        getArtifactGraph: getArtifactGraph,
    };

    function getArtifactGraph(artifact_id) {
        return rest
            .one("testmanagement_nodes", artifact_id)
            .get()
            .then(
                function (response) {
                    return response.data;
                },
                function (error) {
                    return error.data;
                }
            );
    }
}
