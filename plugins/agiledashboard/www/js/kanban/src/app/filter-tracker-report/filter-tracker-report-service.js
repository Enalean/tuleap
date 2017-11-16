export default FilterTrackerReportService;

FilterTrackerReportService.$inject = [
    'SharedPropertiesService'
];

function FilterTrackerReportService(
    SharedPropertiesService
) {
    let self = this;

    Object.assign(self, {
        filters_tracker_report: undefined,
        setFiltersTrackerReport,
        getFiltersTrackerReport,
        getSelectedFilterTrackerReportId,
        isFiltersTrackerReportSelected,
        areCardsAndWIPUpdated,
        isWIPUpdated,
        areNotCardsAndWIPUpdated,
        isNotWIPUpdated
    });

    function setFiltersTrackerReport(filters_tracker_report) {
        self.filters_tracker_report = filters_tracker_report;
    }

    function getFiltersTrackerReport() {
        return self.filters_tracker_report;
    }

    function getSelectedFilterTrackerReportId() {
        if (! self.filters_tracker_report) {
            return 0;
        }

        const selected_filter_tracker_report = self.filters_tracker_report.find(filter => filter.selected);

        if (selected_filter_tracker_report) {
            return parseInt(selected_filter_tracker_report.id, 10);
        }

        return 0;
    }

    function isFiltersTrackerReportSelected() {
        if (! self.filters_tracker_report) {
            return false;
        }
        return self.filters_tracker_report.some(filter => filter.selected);
    }

    function areCardsAndWIPUpdated() {
        return (SharedPropertiesService.thereIsNodeServerAddress() &&
            ! isFiltersTrackerReportSelected());
    }

    function isWIPUpdated() {
        return (! SharedPropertiesService.thereIsNodeServerAddress() &&
            ! isFiltersTrackerReportSelected());
    }

    function areNotCardsAndWIPUpdated() {
        return (SharedPropertiesService.thereIsNodeServerAddress() &&
            isFiltersTrackerReportSelected());
    }

    function isNotWIPUpdated() {
        return (! SharedPropertiesService.thereIsNodeServerAddress() &&
            isFiltersTrackerReportSelected());
    }
}