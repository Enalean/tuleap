export { canChooseArtifactsParent };

function canChooseArtifactsParent(tracker, linked_artifact) {
    if (!tracker.parent) {
        return false;
    }

    if (!linked_artifact) {
        return true;
    }

    return (
        linked_artifact.artifact !== undefined &&
        tracker.parent !== undefined &&
        linked_artifact.artifact.tracker.id !== tracker.parent.id
    );
}
