export default FilterTrackerReportController;

FilterTrackerReportController.$inject = [
    "$window",
    "FilterTrackerReportService",
    "SharedPropertiesService",
];

function FilterTrackerReportController(
    $window,
    FilterTrackerReportService,
    SharedPropertiesService
) {
    const self = this;

    Object.assign(self, {
        selected_item: FilterTrackerReportService.getSelectedFilterTrackerReportId().toString(),

        changeFilter,
        displaySelectbox,
        getSelectableReports: FilterTrackerReportService.getSelectableReports,
    });

    function changeFilter() {
        const search_params = new URLSearchParams($window.location.search);

        if (parseInt(self.selected_item, 10)) {
            search_params.set("tracker_report_id", self.selected_item);
        } else {
            search_params.delete("tracker_report_id");
        }

        $window.location.search = search_params.toString();
    }

    function displaySelectbox() {
        return (
            self.getSelectableReports().length > 0 && SharedPropertiesService.getWidgetId() === 0
        );
    }
}
