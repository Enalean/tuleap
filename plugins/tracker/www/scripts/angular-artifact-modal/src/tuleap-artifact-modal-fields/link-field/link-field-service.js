import { isInCreationMode } from '../../modal-creation-mode-state.js';

export {
    canChooseArtifactsParent
};

function canChooseArtifactsParent(tracker, linked_artifact) {
    if (! isInCreationMode()) {
        return false;
    }

    if (! tracker.parent) {
        return false;
    }

    if (! linked_artifact) {
        return true;
    }

    return (
        linked_artifact.artifact !== undefined
        && tracker.parent !== undefined
        && linked_artifact.artifact.tracker.id !== tracker.parent.id
    );
}
