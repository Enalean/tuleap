import _ from 'lodash';

export default GraphCtrl;

GraphCtrl.$inject = ['$state', 'ArtifactLinksGraphService'];

function GraphCtrl($state, ArtifactLinksGraphService) {
    var self        = this,
        artifact_id = $state.params.id;

    _.extend(self, {
        graphd3: undefined,
        errors : [],
        title  : ''
    });

    init(artifact_id);

    function init(artifact_id) {
        ArtifactLinksGraphService.showGraph(artifact_id).then(function(model) {
            self.graphd3 = model.graph;
            self.errors  = model.errors;
            self.title   = model.title;
        });
    }
}

