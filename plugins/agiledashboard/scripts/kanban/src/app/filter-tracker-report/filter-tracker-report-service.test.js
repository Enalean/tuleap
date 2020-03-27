import kanban_module from "../app.js";
import angular from "angular";
import "angular-mocks";

describe("FilterTrackerReportService -", () => {
    let FilterTrackerReportService, SharedPropertiesService;

    beforeEach(() => {
        angular.mock.module(kanban_module);

        angular.mock.inject(function (_FilterTrackerReportService_, _SharedPropertiesService_) {
            FilterTrackerReportService = _FilterTrackerReportService_;
            SharedPropertiesService = _SharedPropertiesService_;
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
            const selected_tracker_report_id = FilterTrackerReportService.getSelectedFilterTrackerReportId();
            expect(selected_tracker_report_id).toEqual(306);
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
            const selected_tracker_report_id = FilterTrackerReportService.getSelectedFilterTrackerReportId();
            expect(selected_tracker_report_id).toEqual(0);
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
            expect(FilterTrackerReportService.isFiltersTrackerReportSelected()).toEqual(true);
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
            expect(FilterTrackerReportService.isFiltersTrackerReportSelected()).toEqual(false);
        });
    });

    describe("areCardsAndWIPUpdated() -", () => {
        it("Given node.js server address and a selected report, then false will be returned", () => {
            jest.spyOn(SharedPropertiesService, "thereIsNodeServerAddress").mockReturnValue(true);
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
            expect(FilterTrackerReportService.areCardsAndWIPUpdated()).toEqual(false);
        });

        it("Given empty node.js server address and a selected report, then false will be returned", () => {
            jest.spyOn(SharedPropertiesService, "thereIsNodeServerAddress").mockReturnValue(false);
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
            expect(FilterTrackerReportService.areCardsAndWIPUpdated()).toEqual(false);
        });

        it("Given empty node.js server address and no selected report, then false will be returned", () => {
            jest.spyOn(SharedPropertiesService, "thereIsNodeServerAddress").mockReturnValue(false);
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
            expect(FilterTrackerReportService.areCardsAndWIPUpdated()).toEqual(false);
        });

        it("Given node.js server address and no selected report, then true will be returned", () => {
            jest.spyOn(SharedPropertiesService, "thereIsNodeServerAddress").mockReturnValue(true);
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
            expect(FilterTrackerReportService.areCardsAndWIPUpdated()).toEqual(true);
        });
    });

    describe("isWIPUpdated() -", () => {
        it("Given empty node.js server address and a selected report, then false will be returned", () => {
            jest.spyOn(SharedPropertiesService, "thereIsNodeServerAddress").mockReturnValue(false);
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
            expect(FilterTrackerReportService.isWIPUpdated()).toEqual(false);
        });

        it("Given empty node.js server address and no selected report, then true will be returned", () => {
            jest.spyOn(SharedPropertiesService, "thereIsNodeServerAddress").mockReturnValue(false);
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
            expect(FilterTrackerReportService.isWIPUpdated()).toEqual(true);
        });

        it("Given node.js server address and a selected report, then false will be returned", () => {
            jest.spyOn(SharedPropertiesService, "thereIsNodeServerAddress").mockReturnValue(true);
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
            expect(FilterTrackerReportService.isWIPUpdated()).toEqual(false);
        });

        it("Given node.js server address and no selected report, then false will be returned", () => {
            jest.spyOn(SharedPropertiesService, "thereIsNodeServerAddress").mockReturnValue(true);
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
            expect(FilterTrackerReportService.isWIPUpdated()).toEqual(false);
        });
    });

    describe("areNotCardsAndWIPUpdated() -", () => {
        it("Given node.js server address and a selected report, then true will be returned", () => {
            jest.spyOn(SharedPropertiesService, "thereIsNodeServerAddress").mockReturnValue(true);
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
            expect(FilterTrackerReportService.areNotCardsAndWIPUpdated()).toEqual(true);
        });

        it("Given empty node.js server address and a selected report, then false will be returned", () => {
            jest.spyOn(SharedPropertiesService, "thereIsNodeServerAddress").mockReturnValue(false);
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
            expect(FilterTrackerReportService.areNotCardsAndWIPUpdated()).toEqual(false);
        });

        it("Given empty node.js server address and no selected report, then false will be returned", () => {
            jest.spyOn(SharedPropertiesService, "thereIsNodeServerAddress").mockReturnValue(false);
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
            expect(FilterTrackerReportService.areNotCardsAndWIPUpdated()).toEqual(false);
        });

        it("Given node.js server address and no selected report, then false will be returned", () => {
            jest.spyOn(SharedPropertiesService, "thereIsNodeServerAddress").mockReturnValue(true);
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
            expect(FilterTrackerReportService.areNotCardsAndWIPUpdated()).toEqual(false);
        });
    });

    describe("isNotWIPUpdated() -", () => {
        it("Given empty node.js server address and a selected report, then true will be returned", () => {
            jest.spyOn(SharedPropertiesService, "thereIsNodeServerAddress").mockReturnValue(false);
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
            expect(FilterTrackerReportService.isNotWIPUpdated()).toEqual(true);
        });

        it("Given empty node.js server address and no selected report, then false will be returned", () => {
            jest.spyOn(SharedPropertiesService, "thereIsNodeServerAddress").mockReturnValue(false);
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
            expect(FilterTrackerReportService.isNotWIPUpdated()).toEqual(false);
        });

        it("Given node.js server address and a selected report, then false will be returned", () => {
            jest.spyOn(SharedPropertiesService, "thereIsNodeServerAddress").mockReturnValue(true);
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
            expect(FilterTrackerReportService.isNotWIPUpdated()).toEqual(false);
        });

        it("Given node.js server address and no selected report, then false will be returned", () => {
            jest.spyOn(SharedPropertiesService, "thereIsNodeServerAddress").mockReturnValue(true);
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
            expect(FilterTrackerReportService.isNotWIPUpdated()).toEqual(false);
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
