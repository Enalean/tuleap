export default FilterTrackerReportService;

function FilterTrackerReportService() {
    const self = this;

    Object.assign(self, {
        tracker_reports: [],
        selectable_reports: [],

        getSelectedFilterTrackerReportId,
        isFiltersTrackerReportSelected,
        changeSelectableReports,
        areCardsAndWIPUpdated,
        areNotCardsAndWIPUpdated,
        initTrackerReports,
        getTrackerReports() {
            return self.tracker_reports;
        },
        getSelectableReports() {
            return self.selectable_reports;
        },
    });

    function initTrackerReports(tracker_reports) {
        self.tracker_reports = tracker_reports;
        self.selectable_reports = self.tracker_reports.filter((report) => report.selectable);
    }

    function getSelectedFilterTrackerReportId() {
        if (self.selectable_reports.length === 0) {
            return 0;
        }

        const selected_filter_tracker_report = self.selectable_reports.find(
            (report) => report.selected
        );

        if (selected_filter_tracker_report) {
            return parseInt(selected_filter_tracker_report.id, 10);
        }

        return 0;
    }

    function isFiltersTrackerReportSelected() {
        if (self.selectable_reports.length === 0) {
            return false;
        }
        return self.selectable_reports.some((report) => report.selected);
    }

    function changeSelectableReports(report_ids) {
        self.selectable_reports = self.tracker_reports.filter(({ id }) => report_ids.includes(id));
    }

    function areCardsAndWIPUpdated() {
        return !isFiltersTrackerReportSelected();
    }

    function areNotCardsAndWIPUpdated() {
        return isFiltersTrackerReportSelected();
    }
}
