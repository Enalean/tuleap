export { canChooseArtifactsParent };

function canChooseArtifactsParent(tracker, linked_artifact, has_current_project_parents) {
    if (!tracker.parent && !has_current_project_parents) {
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
