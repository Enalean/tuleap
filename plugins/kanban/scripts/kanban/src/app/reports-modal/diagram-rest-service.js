export default DiagramRestService;

DiagramRestService.$inject = ["$http", "SharedPropertiesService", "FilterTrackerReportService"];

function DiagramRestService($http, SharedPropertiesService, FilterTrackerReportService) {
    const self = this;

    self.getCumulativeFlowDiagram = getCumulativeFlowDiagram;

    function getCumulativeFlowDiagram(kanban_id, start_date, end_date, interval_between_point) {
        const TIMEOUT_IN_MILLISECONDS = 20000;

        let query_params = {
            start_date,
            end_date,
            interval_between_point,
        };

        augmentQueryParamsWithFilterTrackerReport(query_params);

        return $http
            .get("/api/v1/kanban/" + kanban_id + "/cumulative_flow", {
                headers: {
                    "X-Client-UUID": SharedPropertiesService.getUUID(),
                },
                params: query_params,
                timeout: TIMEOUT_IN_MILLISECONDS,
            })
            .then(({ data }) => data);
    }

    function augmentQueryParamsWithFilterTrackerReport(query_params) {
        const selected_filter_tracker_report_id =
            FilterTrackerReportService.getSelectedFilterTrackerReportId();

        if (selected_filter_tracker_report_id) {
            query_params.query = { tracker_report_id: selected_filter_tracker_report_id };
        }
    }
}
