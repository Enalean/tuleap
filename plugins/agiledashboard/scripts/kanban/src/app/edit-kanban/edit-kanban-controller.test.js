import kanban_module from "../app.js";
import angular from "angular";
import "angular-mocks";
import BaseController from "./edit-kanban-controller.js";

describe("EditKanbanController -", () => {
    let $q,
        $scope,
        EditKanbanController,
        KanbanService,
        FilterTrackerReportService,
        SharedPropertiesService,
        RestErrorService,
        modal_instance;

    beforeEach(() => {
        angular.mock.module(kanban_module);

        let $controller, $rootScope;

        angular.mock.inject(function (
            _$controller_,
            _$rootScope_,
            _$q_,
            _KanbanService_,
            _FilterTrackerReportService_,
            _SharedPropertiesService_,
            _RestErrorService_
        ) {
            $controller = _$controller_;
            $rootScope = _$rootScope_;
            $q = _$q_;
            KanbanService = _KanbanService_;
            FilterTrackerReportService = _FilterTrackerReportService_;
            SharedPropertiesService = _SharedPropertiesService_;
            RestErrorService = _RestErrorService_;
        });

        $scope = $rootScope.$new();

        modal_instance = {
            tlp_modal: { hide: jest.fn() },
        };
        const rebuild_scrollbars = angular.noop;

        jest.spyOn(SharedPropertiesService, "getKanban").mockReturnValue({
            label: "boxman",
            tracker: {
                id: 48,
                label: "pinkwort",
            },
        });

        jest.spyOn(RestErrorService, "reload").mockImplementation(() => {});

        EditKanbanController = $controller(BaseController, {
            $scope,
            KanbanService,
            FilterTrackerReportService,
            modal_instance,
            rebuild_scrollbars,
        });
    });

    describe("saveReports() -", () => {
        let selectable_report_ids;

        beforeEach(() => {
            selectable_report_ids = [7, 63];
            Object.assign(EditKanbanController, {
                kanban: { id: 48 },
                selectable_report_ids,
            });
            jest.spyOn(KanbanService, "updateSelectableReports").mockImplementation(() => {});
            jest.spyOn(
                FilterTrackerReportService,
                "changeSelectableReports"
            ).mockImplementation(() => {});
        });

        it("Given that we selected reports to be selectable, then the reports' ids will be saved in backend and will be updated in the dedicated service", () => {
            KanbanService.updateSelectableReports.mockReturnValue($q.when());

            EditKanbanController.saveReports();
            expect(EditKanbanController.saving).toBe(true);
            $scope.$apply();

            expect(EditKanbanController.saving).toBe(false);
            expect(EditKanbanController.saved).toBe(true);
            expect(KanbanService.updateSelectableReports).toHaveBeenCalledWith(
                EditKanbanController.kanban.id,
                selectable_report_ids
            );
            expect(FilterTrackerReportService.changeSelectableReports).toHaveBeenCalledWith(
                selectable_report_ids
            );
        });

        it("when there is a REST error, then the modal will be closed and the error modal will be shown", () => {
            KanbanService.updateSelectableReports.mockReturnValue($q.reject({ status: 500 }));

            EditKanbanController.saveReports();
            $scope.$apply();

            expect(modal_instance.tlp_modal.hide).toHaveBeenCalled();
            expect(RestErrorService.reload).toHaveBeenCalled();
        });
    });
});
