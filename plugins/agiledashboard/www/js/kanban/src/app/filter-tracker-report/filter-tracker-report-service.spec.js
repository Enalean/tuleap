import kanban_module from '../app.js';
import angular from 'angular';
import 'angular-mocks';

describe("FilterTrackerReportService -", () => {
    let FilterTrackerReportService,
        SharedPropertiesService;

    beforeEach(() => {
        angular.mock.module(kanban_module);

        angular.mock.inject(function(
            _FilterTrackerReportService_,
            _SharedPropertiesService_
        ) {
            FilterTrackerReportService = _FilterTrackerReportService_;
            SharedPropertiesService    = _SharedPropertiesService_;
        });
    });

    describe("getSelectedFilterTrackerReportId() -", () => {
        it("Given filters tracker report array with a selected report, then the selected filter tracker report will be retrieved", function() {
            FilterTrackerReportService.filters_tracker_report = [
                {
                    id              : 305,
                    description     : 'The system default artifact report',
                    name            : 'Default',
                    parent_report_id: '293'
                },
                {
                    id              : 306,
                    description     : 'The system personal artifact report',
                    name            : 'Personal',
                    parent_report_id: '296',
                    selected        : true
                }
            ];
            const selected_tracker_report_id = FilterTrackerReportService.getSelectedFilterTrackerReportId();
            expect(selected_tracker_report_id).toEqual(306);
        });

        it("Given filters tracker report array with no selected report, then 0 is returned", function() {
            FilterTrackerReportService.filters_tracker_report = [
                {
                    id              : 305,
                    description     : 'The system default artifact report',
                    name            : 'Default',
                    parent_report_id: '293'
                },
                {
                    id              : 306,
                    description     : 'The system personal artifact report',
                    name            : 'Personal',
                    parent_report_id: '296'
                }
            ];
            const selected_tracker_report_id = FilterTrackerReportService.getSelectedFilterTrackerReportId();
            expect(selected_tracker_report_id).toEqual(0);
        });
    });

    describe("isFiltersTrackerReportSelected() -", () => {
        it("Given filters tracker report array with a selected report, then true is returned", function() {
            FilterTrackerReportService.filters_tracker_report = [
                {
                    id              : 305,
                    description     : 'The system default artifact report',
                    name            : 'Default',
                    parent_report_id: '293'
                },
                {
                    id              : 306,
                    description     : 'The system personal artifact report',
                    name            : 'Personal',
                    parent_report_id: '296',
                    selected        : true
                }
            ];
            expect(FilterTrackerReportService.isFiltersTrackerReportSelected()).toEqual(true);
        });

        it("Given filters tracker report array with no selected report, then false is returned", function() {
            FilterTrackerReportService.filters_tracker_report = [
                {
                    id              : 305,
                    description     : 'The system default artifact report',
                    name            : 'Default',
                    parent_report_id: '293'
                },
                {
                    id              : 306,
                    description     : 'The system personal artifact report',
                    name            : 'Personal',
                    parent_report_id: '296'
                }
            ];
            expect(FilterTrackerReportService.isFiltersTrackerReportSelected()).toEqual(false);
        });
    });

    describe("areCardsAndWIPUpdated() -", () => {
        it("Given node.js server address and a selected report, then false will be returned", function() {
            spyOn(SharedPropertiesService, "thereIsNodeServerAddress").and.returnValue(true);
            FilterTrackerReportService.filters_tracker_report = [
                {
                    id              : 305,
                    description     : 'The system default artifact report',
                    name            : 'Default',
                    parent_report_id: '293'
                },
                {
                    id              : 306,
                    description     : 'The system personal artifact report',
                    name            : 'Personal',
                    parent_report_id: '296',
                    selected        : true
                }
            ];
            expect(FilterTrackerReportService.areCardsAndWIPUpdated()).toEqual(false);
        });

        it("Given empty node.js server address and a selected report, then false will be returned", function() {
            spyOn(SharedPropertiesService, "thereIsNodeServerAddress").and.returnValue(false);
            FilterTrackerReportService.filters_tracker_report = [
                {
                    id              : 305,
                    description     : 'The system default artifact report',
                    name            : 'Default',
                    parent_report_id: '293'
                },
                {
                    id              : 306,
                    description     : 'The system personal artifact report',
                    name            : 'Personal',
                    parent_report_id: '296',
                    selected        : true
                }
            ];
            expect(FilterTrackerReportService.areCardsAndWIPUpdated()).toEqual(false);
        });

        it("Given empty node.js server address and no selected report, then false will be returned", function() {
            spyOn(SharedPropertiesService, "thereIsNodeServerAddress").and.returnValue(false);
            FilterTrackerReportService.filters_tracker_report = [
                {
                    id              : 305,
                    description     : 'The system default artifact report',
                    name            : 'Default',
                    parent_report_id: '293'
                },
                {
                    id              : 306,
                    description     : 'The system personal artifact report',
                    name            : 'Personal',
                    parent_report_id: '296'
                }
            ];
            expect(FilterTrackerReportService.areCardsAndWIPUpdated()).toEqual(false);
        });

        it("Given node.js server address and no selected report, then true will be returned", function() {
            spyOn(SharedPropertiesService, "thereIsNodeServerAddress").and.returnValue(true);
            FilterTrackerReportService.filters_tracker_report = [
                {
                    id              : 305,
                    description     : 'The system default artifact report',
                    name            : 'Default',
                    parent_report_id: '293'
                },
                {
                    id              : 306,
                    description     : 'The system personal artifact report',
                    name            : 'Personal',
                    parent_report_id: '296'
                }
            ];
            expect(FilterTrackerReportService.areCardsAndWIPUpdated()).toEqual(true);
        });
    });

    describe("isWIPUpdated() -", () => {
        it("Given empty node.js server address and a selected report, then false will be returned", function() {
            spyOn(SharedPropertiesService, "thereIsNodeServerAddress").and.returnValue(false);
            FilterTrackerReportService.filters_tracker_report = [
                {
                    id              : 305,
                    description     : 'The system default artifact report',
                    name            : 'Default',
                    parent_report_id: '293'
                },
                {
                    id              : 306,
                    description     : 'The system personal artifact report',
                    name            : 'Personal',
                    parent_report_id: '296',
                    selected        : true
                }
            ];
            expect(FilterTrackerReportService.isWIPUpdated(FilterTrackerReportService.filters_tracker_report)).toEqual(false);
        });

        it("Given empty node.js server address and no selected report, then true will be returned", function() {
            spyOn(SharedPropertiesService, "thereIsNodeServerAddress").and.returnValue(false);
            FilterTrackerReportService.filters_tracker_report = [
                {
                    id              : 305,
                    description     : 'The system default artifact report',
                    name            : 'Default',
                    parent_report_id: '293'
                },
                {
                    id              : 306,
                    description     : 'The system personal artifact report',
                    name            : 'Personal',
                    parent_report_id: '296'
                }
            ];
            expect(FilterTrackerReportService.isWIPUpdated(FilterTrackerReportService.filters_tracker_report)).toEqual(true);
        });

        it("Given node.js server address and a selected report, then false will be returned", function() {
            spyOn(SharedPropertiesService, "thereIsNodeServerAddress").and.returnValue(true);
            FilterTrackerReportService.filters_tracker_report = [
                {
                    id              : 305,
                    description     : 'The system default artifact report',
                    name            : 'Default',
                    parent_report_id: '293'
                },
                {
                    id              : 306,
                    description     : 'The system personal artifact report',
                    name            : 'Personal',
                    parent_report_id: '296',
                    selected        : true
                }
            ];
            expect(FilterTrackerReportService.isWIPUpdated(FilterTrackerReportService.filters_tracker_report)).toEqual(false);
        });

        it("Given node.js server address and no selected report, then false will be returned", function() {
            spyOn(SharedPropertiesService, "thereIsNodeServerAddress").and.returnValue(true);
            FilterTrackerReportService.filters_tracker_report = [
                {
                    id              : 305,
                    description     : 'The system default artifact report',
                    name            : 'Default',
                    parent_report_id: '293'
                },
                {
                    id              : 306,
                    description     : 'The system personal artifact report',
                    name            : 'Personal',
                    parent_report_id: '296'
                }
            ];
            expect(FilterTrackerReportService.isWIPUpdated(FilterTrackerReportService.filters_tracker_report)).toEqual(false);
        });
    });

    describe("areNotCardsAndWIPUpdated() -", () => {
        it("Given node.js server address and a selected report, then true will be returned", function() {
            spyOn(SharedPropertiesService, "thereIsNodeServerAddress").and.returnValue(true);
            FilterTrackerReportService.filters_tracker_report = [
                {
                    id              : 305,
                    description     : 'The system default artifact report',
                    name            : 'Default',
                    parent_report_id: '293'
                },
                {
                    id              : 306,
                    description     : 'The system personal artifact report',
                    name            : 'Personal',
                    parent_report_id: '296',
                    selected        : true
                }
            ];
            expect(FilterTrackerReportService.areNotCardsAndWIPUpdated()).toEqual(true);
        });

        it("Given empty node.js server address and a selected report, then false will be returned", function() {
            spyOn(SharedPropertiesService, "thereIsNodeServerAddress").and.returnValue(false);
            FilterTrackerReportService.filters_tracker_report = [
                {
                    id              : 305,
                    description     : 'The system default artifact report',
                    name            : 'Default',
                    parent_report_id: '293'
                },
                {
                    id              : 306,
                    description     : 'The system personal artifact report',
                    name            : 'Personal',
                    parent_report_id: '296',
                    selected        : true
                }
            ];
            expect(FilterTrackerReportService.areNotCardsAndWIPUpdated()).toEqual(false);
        });

        it("Given empty node.js server address and no selected report, then false will be returned", function() {
            spyOn(SharedPropertiesService, "thereIsNodeServerAddress").and.returnValue(false);
            FilterTrackerReportService.filters_tracker_report = [
                {
                    id              : 305,
                    description     : 'The system default artifact report',
                    name            : 'Default',
                    parent_report_id: '293'
                },
                {
                    id              : 306,
                    description     : 'The system personal artifact report',
                    name            : 'Personal',
                    parent_report_id: '296'
                }
            ];
            expect(FilterTrackerReportService.areNotCardsAndWIPUpdated()).toEqual(false);
        });

        it("Given node.js server address and no selected report, then false will be returned", function() {
            spyOn(SharedPropertiesService, "thereIsNodeServerAddress").and.returnValue(true);
            FilterTrackerReportService.filters_tracker_report = [
                {
                    id              : 305,
                    description     : 'The system default artifact report',
                    name            : 'Default',
                    parent_report_id: '293'
                },
                {
                    id              : 306,
                    description     : 'The system personal artifact report',
                    name            : 'Personal',
                    parent_report_id: '296'
                }
            ];
            expect(FilterTrackerReportService.areNotCardsAndWIPUpdated()).toEqual(false);
        });
    });

    describe("isNotWIPUpdated() -", () => {
        it("Given empty node.js server address and a selected report, then true will be returned", function() {
            spyOn(SharedPropertiesService, "thereIsNodeServerAddress").and.returnValue(false);
            FilterTrackerReportService.filters_tracker_report = [
                {
                    id              : 305,
                    description     : 'The system default artifact report',
                    name            : 'Default',
                    parent_report_id: '293'
                },
                {
                    id              : 306,
                    description     : 'The system personal artifact report',
                    name            : 'Personal',
                    parent_report_id: '296',
                    selected        : true
                }
            ];
            expect(FilterTrackerReportService.isNotWIPUpdated(FilterTrackerReportService.filters_tracker_report)).toEqual(true);
        });

        it("Given empty node.js server address and no selected report, then false will be returned", function() {
            spyOn(SharedPropertiesService, "thereIsNodeServerAddress").and.returnValue(false);
            FilterTrackerReportService.filters_tracker_report = [
                {
                    id              : 305,
                    description     : 'The system default artifact report',
                    name            : 'Default',
                    parent_report_id: '293'
                },
                {
                    id              : 306,
                    description     : 'The system personal artifact report',
                    name            : 'Personal',
                    parent_report_id: '296'
                }
            ];
            expect(FilterTrackerReportService.isNotWIPUpdated(FilterTrackerReportService.filters_tracker_report)).toEqual(false);
        });

        it("Given node.js server address and a selected report, then false will be returned", function() {
            spyOn(SharedPropertiesService, "thereIsNodeServerAddress").and.returnValue(true);
            FilterTrackerReportService.filters_tracker_report = [
                {
                    id              : 305,
                    description     : 'The system default artifact report',
                    name            : 'Default',
                    parent_report_id: '293'
                },
                {
                    id              : 306,
                    description     : 'The system personal artifact report',
                    name            : 'Personal',
                    parent_report_id: '296',
                    selected        : true
                }
            ];
            expect(FilterTrackerReportService.isNotWIPUpdated(FilterTrackerReportService.filters_tracker_report)).toEqual(false);
        });

        it("Given node.js server address and no selected report, then false will be returned", function() {
            spyOn(SharedPropertiesService, "thereIsNodeServerAddress").and.returnValue(true);
            FilterTrackerReportService.filters_tracker_report = [
                {
                    id              : 305,
                    description     : 'The system default artifact report',
                    name            : 'Default',
                    parent_report_id: '293'
                },
                {
                    id              : 306,
                    description     : 'The system personal artifact report',
                    name            : 'Personal',
                    parent_report_id: '296'
                }
            ];
            expect(FilterTrackerReportService.isNotWIPUpdated(FilterTrackerReportService.filters_tracker_report)).toEqual(false);
        });
    });
});
