import kanban_module from '../app.js';
import angular from 'angular';
import 'angular-mocks';

describe("DiagramRestService -", function() {
    var DiagramRestService,
        SharedPropertiesService,
        $httpBackend;

    beforeEach(function() {
        angular.mock.module(kanban_module);

        angular.mock.inject(function(
            _$httpBackend_,
            _SharedPropertiesService_,
            _DiagramRestService_
        ) {
            $httpBackend            = _$httpBackend_;
            SharedPropertiesService = _SharedPropertiesService_;
            DiagramRestService      = _DiagramRestService_;
        });

        spyOn(SharedPropertiesService, "getUUID");

        installPromiseMatchers();
    });

    afterEach(function() {
        $httpBackend.verifyNoOutstandingExpectation();
        $httpBackend.verifyNoOutstandingRequest();
    });

    describe("getCumulativeFlowDiagram() -", function() {
        it("Given a kanban id, a start date, an end date and an interval between points, when I get the cumulative flow diagram for this period, then a GET request will be made with the correct parameters and a promise will be resolved with the data", function() {
            var UUID = '20f15d7f-f8ae-465a-aa59-cc9d77e5e372';
            SharedPropertiesService.getUUID.and.returnValue(UUID);

            var kanban_id               = 6;
            var start_date              = '2010-09-06';
            var end_date                = '2016-11-26';
            var interval_between_points = 73;

            var cumulative_flow_data = {
                columns: [
                    { id: 37 },
                    { id: 89 }
                ]
            };

            var expected_url = '/api/v1/kanban/' + kanban_id + '/cumulative_flow' +
                '?end_date=' + end_date +
                '&interval_between_point=' + interval_between_points +
                '&start_date=' + start_date;

            $httpBackend.expectGET(expected_url, {
                Accept         : 'application/json, text/plain, */*',
                'X-Client-UUID': UUID,
            }).respond(cumulative_flow_data);

            var promise = DiagramRestService.getCumulativeFlowDiagram(
                kanban_id,
                start_date,
                end_date,
                interval_between_points
            );
            $httpBackend.flush();

            expect(promise).toBeResolvedWith(cumulative_flow_data);
        });
    });
});
