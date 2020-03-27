import kanban_module from "../app.js";
import reports_module from "./reports-modal-test.js";
import angular from "angular";
import "angular-mocks";
import BaseController from "./reports-modal-controller.js";

describe("ReportsModalController -", function () {
    var ReportsModalController,
        $scope,
        $q,
        SharedPropertiesService,
        DiagramRestService,
        kanban_id,
        kanban_label,
        tlp_modal;

    beforeEach(() => {
        angular.mock.module(reports_module);
        angular.mock.module(kanban_module);

        var $controller, $rootScope;

        angular.mock.inject(function (
            _$controller_,
            _$q_,
            _$rootScope_,
            _SharedPropertiesService_,
            _DiagramRestService_
        ) {
            $controller = _$controller_;
            $q = _$q_;
            $rootScope = _$rootScope_;
            SharedPropertiesService = _SharedPropertiesService_;
            DiagramRestService = _DiagramRestService_;
        });

        $scope = $rootScope.$new();

        kanban_id = 2;
        kanban_label = "Italy Kanban";
        tlp_modal = jest.fn();

        jest.spyOn(SharedPropertiesService, "getKanban").mockReturnValue({
            id: kanban_id,
            label: kanban_label,
            columns: [],
            backlog: {
                id: "backlog",
                label: "Backlog",
            },
            archive: {
                id: "archive",
                label: "Archive",
            },
        });
        jest.spyOn(DiagramRestService, "getCumulativeFlowDiagram").mockReturnValue(
            $q(angular.noop)
        );

        ReportsModalController = $controller(BaseController, {
            $scope: $scope,
            modal_instance: { tlp_modal: tlp_modal },
            SharedPropertiesService: SharedPropertiesService,
            DiagramRestService: DiagramRestService,
        });
    });

    describe("init() -", function () {
        it(`when the controller is created,
            then the cumulative flow diagram data for last week will be retrieved,
            a loading flag will be set
            and Chart.js data will be set`, function () {
            var cumulative_flow_data = {
                columns: [
                    {
                        id: "backlog",
                        label: "Backlog",
                        values: [
                            {
                                start_date: "2012-12-07",
                                kanban_items_count: 4,
                            },
                            {
                                start_date: "2012-09-02",
                                kanban_items_count: 5,
                            },
                        ],
                    },
                    {
                        id: "archive",
                        label: "Archive",
                        values: [
                            {
                                start_date: "2012-12-07",
                                kanban_items_count: 3,
                            },
                            {
                                start_date: "2012-09-02",
                                kanban_items_count: 9,
                            },
                        ],
                    },
                ],
            };
            DiagramRestService.getCumulativeFlowDiagram.mockReturnValue(
                $q.when(cumulative_flow_data)
            );

            ReportsModalController.$onInit();
            expect(ReportsModalController.loading).toBe(true);

            $scope.$apply();

            expect(ReportsModalController.loading).toBe(false);

            var YYYY_MM_DD_regexp = /^\d{4}-\d{2}-\d{2}$/;
            var interval_between_points = 1;
            expect(DiagramRestService.getCumulativeFlowDiagram).toHaveBeenCalledWith(
                kanban_id,
                expect.stringMatching(YYYY_MM_DD_regexp),
                expect.stringMatching(YYYY_MM_DD_regexp),
                interval_between_points
            );

            expect(ReportsModalController.kanban_label).toEqual(kanban_label);
            expect(ReportsModalController.data).toEqual([
                {
                    id: "backlog",
                    label: "Backlog",
                    values: [
                        { start_date: "2012-12-07", kanban_items_count: 4 },
                        { start_date: expect.any(String), kanban_items_count: 5 },
                    ],
                },
                {
                    id: "archive",
                    label: "Archive",
                    values: [
                        { start_date: "2012-12-07", kanban_items_count: 3 },
                        { start_date: expect.any(String), kanban_items_count: 9 },
                    ],
                },
            ]);
        });
    });
});
