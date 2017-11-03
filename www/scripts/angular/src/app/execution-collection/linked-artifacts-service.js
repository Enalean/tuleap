export default LinkedArtifactsService;

LinkedArtifactsService.$inject = [
    'ExecutionRestService',
    'SharedPropertiesService'
];

function LinkedArtifactsService(
    ExecutionRestService,
    SharedPropertiesService
) {
    const self = this;
    self.getAllLinkedIssues = getAllLinkedIssues;

    function getAllLinkedIssues(execution, offset, progress_callback) {
        const limit            = 50;
        const issue_tracker_id = SharedPropertiesService.getIssueTrackerId();

        return ExecutionRestService.getLinkedArtifacts(execution, limit, offset)
        .then(({ collection, total }) => {
            const linked_issues = collection.filter(artifact => artifact.tracker.id === issue_tracker_id);
            progress_callback(linked_issues);

            const is_recursion_needed = (offset + limit < total);
            if (is_recursion_needed) {
                return getAllLinkedIssues(
                    execution,
                    offset + limit,
                    progress_callback
                );
            }
        });
    }
}
