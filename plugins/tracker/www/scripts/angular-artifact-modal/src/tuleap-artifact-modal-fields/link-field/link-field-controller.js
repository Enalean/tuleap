import { canChooseArtifactsParent } from './link-field-service.js';

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
        formatArtifact,
        showParentArtifactChoice,
        possible_parent_artifacts: [],
        is_loading               : false
    });

    self.init();

    function init() {
        loadParentArtifactsTitle();
    }

    function formatArtifact(artifact) {
        const tracker_label   = getTrackerLabel(artifact);
        const formatted_title = `${ tracker_label } #${ artifact.id } - ${ artifact.title }`;

        return formatted_title;
    }

    function getTrackerLabel(artifact) {
        if ('tracker' in artifact && 'label' in artifact.tracker) {
            return artifact.tracker.label;
        }

        return '';
    }

    function showParentArtifactChoice() {
        const canChoose = canChooseArtifactsParent(
            self.tracker,
            self.linked_artifact
        );
        return (canChoose && self.possible_parent_artifacts.length > 0);
    }

    function loadParentArtifactsTitle() {
        const canChoose = canChooseArtifactsParent(
            self.tracker,
            self.linked_artifact
        );
        if (canChoose) {
            self.is_loading = true;
            return TuleapArtifactModalRestService.getAllOpenParentArtifacts(self.tracker.id, 1000, 0).then(artifacts => {
                self.possible_parent_artifacts = artifacts;
            }).finally(() => {
                self.is_loading = false;
            });
        }
    }
}
