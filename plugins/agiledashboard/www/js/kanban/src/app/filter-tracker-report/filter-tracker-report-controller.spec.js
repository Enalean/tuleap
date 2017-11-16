import kanban_module from '../app.js';
import angular from 'angular';
import 'angular-mocks';

describe("FilterTrackerReportController -", () => {
    let FilterTrackerReportController,
        $window,
        FilterTrackerReportService;

    beforeEach(function() {
        angular.mock.module(kanban_module, ($provide) => {
            $provide.value('$window', {
                location: {
                    href    : 'https://tuleap-web.tuleap-aio-dev.docker/plugins/agiledashboard/?group_id=126&action=showKanban&id=19&tracker_report_id=305#!/kanban',
                    search  : '?group_id=126&action=showKanban&id=19&tracker_report_id=305'
                }
            });
        });

        var $controller;

        angular.mock.inject(function(
            _$controller_,
            _$window_,
            _FilterTrackerReportService_
        ) {
            $controller                = _$controller_;
            $window                    = _$window_;
            FilterTrackerReportService = _FilterTrackerReportService_;
        });

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

        FilterTrackerReportController = $controller('FilterTrackerReportController', {
            $window: $window,
            FilterTrackerReportService: FilterTrackerReportService
        });
    });

    describe("init() -", () => {
        it("when the controller is created, then the selected filter tracker report will be retrieved", function() {
            expect(FilterTrackerReportController.filters_collection).toEqual([
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
            ]);

            expect(FilterTrackerReportController.selected_item).toEqual('306');
        });
    });

    describe("changeFilter() -", () => {
        it("when the user change the filter to another tracker report, then the search param is modified", function() {
            FilterTrackerReportController.selected_item = '305';
            FilterTrackerReportController.changeFilter();
            expect($window.location.search).toEqual('?group_id=126&action=showKanban&id=19&tracker_report_id=305');
        });

        it("when the user change the filter to none, then the search param is not anymore in the url", function() {
            FilterTrackerReportController.selected_item = '0';
            FilterTrackerReportController.changeFilter();
            expect($window.location.search).toEqual('?group_id=126&action=showKanban&id=19');
        });
    });
});
