import { canChooseArtifactsParent } from './link-field-service.js';
import { isInCreationMode }         from '../../modal-creation-mode-state.js';

export default LinkFieldController;

LinkFieldController.$inject = [
    '$q',
    'TuleapArtifactModalRestService',
];

function LinkFieldController(
    $q,
    TuleapArtifactModalRestService,
) {
    const self = this;

    Object.assign(self, {
        init,
        showParentArtifactChoice,
        loadParentArtifactsTitle,
        hasArtifactAlreadyAParent,
        is_loading               : false,
        parent_artifact          : null,
        possible_parent_artifacts: []
    });

    self.init();

    function init() {
        let promise;
        if (isInCreationMode()) {
            promise = $q.when(self.linked_artifact);
        } else {
            promise = self.hasArtifactAlreadyAParent();
        }

        promise.then(linked_artifact => {
            self.parent_artifact = linked_artifact;

            const canChoose = canChooseArtifactsParent(
                self.tracker,
                self.parent_artifact
            );
            if (canChoose === true) {
                return self.loadParentArtifactsTitle();
            }
        });
    }

    function showParentArtifactChoice() {
        const canChoose = canChooseArtifactsParent(
            self.tracker,
            self.parent_artifact
        );
        return (canChoose && self.possible_parent_artifacts.length > 0);
    }

    function loadParentArtifactsTitle() {
        self.is_loading = true;
        return TuleapArtifactModalRestService.getAllOpenParentArtifacts(self.tracker.id, 1000, 0).then(artifacts => {
            self.possible_parent_artifacts = artifacts.map(artifact => {
                return {
                    id           : artifact.id,
                    formatted_ref: formatArtifact(artifact)
                };
            });
        }).finally(() => {
            self.is_loading = false;
        });
    }

    function hasArtifactAlreadyAParent() {
        self.is_loading = true;
        return TuleapArtifactModalRestService.getFirstReverseIsChildLink(self.artifact_id).then((parent_artifacts) => {
            if (parent_artifacts.length > 0) {
                return parent_artifacts[0];
            }

            return null;
        }).finally(() => {
            self.is_loading = false;
        });
    }

    function formatArtifact(artifact) {
        const tracker_label   = getTrackerLabel(artifact);
        return `${ tracker_label } #${ artifact.id } - ${ artifact.title }`;
    }

    function getTrackerLabel(artifact) {
        if ('tracker' in artifact && 'label' in artifact.tracker) {
            return artifact.tracker.label;
        }

        return '';
    }
}
