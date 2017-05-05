export default DiagramRestService;

DiagramRestService.$inject = [
    '$http',
    'SharedPropertiesService'
];

function DiagramRestService(
    $http,
    SharedPropertiesService
) {
    var self = this;

    self.getCumulativeFlowDiagram = getCumulativeFlowDiagram;

    function getCumulativeFlowDiagram(
        kanban_id,
        start_date,
        end_date,
        interval_between_point
    ) {
        var TIMEOUT_IN_MILLISECONDS = 20000;

        var promise = $http.get('/api/v1/kanban/' + kanban_id + '/cumulative_flow', {
            headers: {
                'X-Client-UUID': SharedPropertiesService.getUUID()
            },
            params: {
                start_date            : start_date,
                end_date              : end_date,
                interval_between_point: interval_between_point
            },
            timeout: TIMEOUT_IN_MILLISECONDS
        })
        .then(function(response) {
            return response.data;
        });

        return promise;
    }
}
