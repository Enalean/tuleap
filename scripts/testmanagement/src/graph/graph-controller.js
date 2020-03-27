export default GraphCtrl;

GraphCtrl.$inject = ["$state", "ArtifactLinksGraphService"];

function GraphCtrl($state, ArtifactLinksGraphService) {
    var self = this,
        artifact_id = $state.params.id;

    Object.assign(self, {
        graphd3: undefined,
        errors: [],
        title: "",
        $onInit,
    });

    function $onInit() {
        ArtifactLinksGraphService.showGraph(artifact_id).then(function (model) {
            self.graphd3 = model.graph;
            self.errors = model.errors;
            self.title = model.title;
        });
    }
}
