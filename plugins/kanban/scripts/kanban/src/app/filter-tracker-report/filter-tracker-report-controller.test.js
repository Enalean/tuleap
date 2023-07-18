import kanban_module from "../app.js";
import angular from "angular";
import "angular-mocks";
import BaseController from "./filter-tracker-report-controller.js";

describe("FilterTrackerReportController -", () => {
    let FilterTrackerReportController, $window, FilterTrackerReportService;

    beforeEach(() => {
        angular.mock.module(kanban_module, ($provide) => {
            $provide.value("$window", {
                location: {
                    href: "https://tuleap-web.tuleap-aio-dev.docker/plugins/agiledashboard/?group_id=126&action=showKanban&id=19&tracker_report_id=305#!/kanban",
                    search: "?tracker_report_id=305",
                },
            });
        });

        let $controller;

        angular.mock.inject(function (_$controller_, _$window_, _FilterTrackerReportService_) {
            $controller = _$controller_;
            $window = _$window_;
            FilterTrackerReportService = _FilterTrackerReportService_;
        });

        FilterTrackerReportService.initTrackerReports([
            {
                id: 305,
                description: "The system default artifact report",
                name: "Default",
                selectable: true,
            },
            {
                id: 306,
                description: "Custom Assigned to me report",
                name: "Assigned to me",
                selectable: true,
                selected: true,
            },
            {
                id: 307,
                description: "bracherer joshi",
                name: "Zoned",
            },
        ]);

        FilterTrackerReportController = $controller(BaseController, {
            $window: $window,
            FilterTrackerReportService: FilterTrackerReportService,
        });
    });

    describe("init() -", () => {
        it("when the controller is created, then the selected filter tracker report will be retrieved", function () {
            expect(FilterTrackerReportController.selected_item).toBe("306");
        });
    });

    describe("changeFilter() -", () => {
        it("when the user change the filter to another tracker report, then the search param is modified", function () {
            FilterTrackerReportController.selected_item = "308";
            FilterTrackerReportController.changeFilter();
            expect($window.location.search).toBe("tracker_report_id=308");
        });

        it("when the user change the filter to none, then the search param is not anymore in the url", function () {
            FilterTrackerReportController.selected_item = "0";
            FilterTrackerReportController.changeFilter();
            expect($window.location.search).toBe("");
        });
    });
});
