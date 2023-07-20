import kanban_module from "../app.js";
import angular from "angular";
import "angular-mocks";

describe("FilterTrackerReportService -", () => {
    let FilterTrackerReportService;

    beforeEach(() => {
        angular.mock.module(kanban_module);

        angular.mock.inject(function (_FilterTrackerReportService_) {
            FilterTrackerReportService = _FilterTrackerReportService_;
        });
    });

    describe("getSelectedFilterTrackerReportId() -", () => {
        it("Given filters tracker report array with a selected report, then the selected filter tracker report will be retrieved", () => {
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
            ]);
            const selected_tracker_report_id =
                FilterTrackerReportService.getSelectedFilterTrackerReportId();
            expect(selected_tracker_report_id).toBe(306);
        });

        it("Given filters tracker report array with no selected report, then 0 is returned", () => {
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
                },
            ]);
            const selected_tracker_report_id =
                FilterTrackerReportService.getSelectedFilterTrackerReportId();
            expect(selected_tracker_report_id).toBe(0);
        });
    });

    describe("isFiltersTrackerReportSelected() -", () => {
        it("Given filters tracker report array with a selected report, then true is returned", () => {
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
            ]);
            expect(FilterTrackerReportService.isFiltersTrackerReportSelected()).toBe(true);
        });

        it("Given filters tracker report array with no selected report, then false is returned", () => {
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
                },
            ]);
            expect(FilterTrackerReportService.isFiltersTrackerReportSelected()).toBe(false);
        });
    });

    describe("areCardsAndWIPUpdated() -", () => {
        it("Given a selected report, then false will be returned", () => {
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
            ]);
            expect(FilterTrackerReportService.areCardsAndWIPUpdated()).toBe(false);
        });

        it("Given no selected report, then true will be returned", () => {
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
                },
            ]);
            expect(FilterTrackerReportService.areCardsAndWIPUpdated()).toBe(true);
        });
    });

    describe("areNotCardsAndWIPUpdated() -", () => {
        it("Given a selected report, then true will be returned", () => {
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
            ]);
            expect(FilterTrackerReportService.areNotCardsAndWIPUpdated()).toBe(true);
        });

        it("Given no selected report, then false will be returned", () => {
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
                },
            ]);
            expect(FilterTrackerReportService.areNotCardsAndWIPUpdated()).toBe(false);
        });
    });

    describe("changeSelectableReports", () => {
        beforeEach(() => {
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
                },
                {
                    id: 307,
                    description: "cacoethic anazoturia",
                    name: "Achill rorqual",
                },
                {
                    id: 308,
                    description: "fundable ironheartedly",
                    name: "Paratactically",
                    selectable: true,
                },
                {
                    id: 309,
                    description: "Diplochlamydeous",
                    name: "Reopposition",
                    selectable: true,
                },
            ]);
        });

        it("Given a list of report ids, then all the reports with those ids will be set to selected and the others will not be selected", () => {
            FilterTrackerReportService.changeSelectableReports([305, 307, 308]);

            const selectable_reports = FilterTrackerReportService.getSelectableReports();
            expect(selectable_reports.map(({ id }) => id)).toEqual([305, 307, 308]);
        });
    });
});
