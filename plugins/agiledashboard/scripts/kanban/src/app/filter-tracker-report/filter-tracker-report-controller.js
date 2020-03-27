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
        const params = $window.location.search.split("?")[1];
        let search_params = params.split("&");
        let index = search_params.findIndex((search_param) => {
            return search_param.split("=")[0] === "tracker_report_id";
        });

        index = index < 0 ? search_params.length : index;

        if (parseInt(self.selected_item, 10)) {
            search_params[index] = "tracker_report_id=" + self.selected_item;
        } else {
            search_params.splice(index, 1);
        }
        $window.location.search = "?" + search_params.join("&");
    }

    function displaySelectbox() {
        return (
            self.getSelectableReports().length > 0 && SharedPropertiesService.getWidgetId() === 0
        );
    }
}
