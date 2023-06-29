import "./filter-tracker-report.tpl.html";
import FilterTrackerReportCtrl from "./filter-tracker-report-controller.js";

export default FilterTrackerReportDirective;

function FilterTrackerReportDirective() {
    return {
        restrict: "E",
        controller: FilterTrackerReportCtrl,
        controllerAs: "filter_tracker_report",
        templateUrl: "filter-tracker-report.tpl.html",
        scope: {},
    };
}
