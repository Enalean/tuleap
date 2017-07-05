angular
    .module('tuleap.artifact-modal')
    .service('TuleapArtifactModalParentService', TuleapArtifactModalParentService);

TuleapArtifactModalParentService.$inject = [

];

function TuleapArtifactModalParentService(

) {
    var self = this;

    _.extend(self, {
        canChooseArtifactsParent: canChooseArtifactsParent
    });

    function canChooseArtifactsParent(parent_tracker, parent_artifact) {
        return (
            Boolean(parent_tracker) &&
            (_.isUndefined(parent_artifact) ||
                (
                    _.has(parent_artifact, 'artifact') &&
                    _.has(parent_artifact.artifact, 'tracker') &&
                    _.has(parent_artifact.artifact.tracker, 'id') &&
                    _.has(parent_tracker, 'id') &&
                    parent_artifact.artifact.tracker.id !== parent_tracker.id
                )
            )
        );
    }
}
